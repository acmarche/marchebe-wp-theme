<?php

namespace AcMarche\Theme\Lib;

/**
 * Resolves the homepage task/service links from [type, blogId, id] to their
 * current permalink, so renaming a page or category never breaks a shortcut.
 * Falls back to the item's hard-coded `url`. Cached for a few hours, since
 * resolving means switching blogs.
 */
class HomeLinks
{
    private const CACHE_PREFIX = 'marche_home_links_';
    private const TTL = 6 * HOUR_IN_SECONDS;

    /**
     * @param array<string, array<int, array<string, mixed>>> $groups e.g. ['tasks' => Data::tasks]
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function resolve(array $groups): array
    {
        // Keyed on the data itself: editing Data::tasks/services invalidates the cache.
        $cacheKey = self::CACHE_PREFIX.md5(serialize($groups));
        $cached = get_site_transient($cacheKey);
        if (is_array($cached) && !WP_DEBUG) {
            return $cached;
        }

        $resolved = [];
        foreach ($groups as $group => $items) {
            foreach ($items as $item) {
                if (isset($item['link']) && ($url = self::permalink(...$item['link']))) {
                    $item['url'] = $url;
                }
                $item['external'] ??= false;
                unset($item['link']);
                $resolved[$group][] = $item;
            }
        }

        set_site_transient($cacheKey, $resolved, self::TTL);

        return $resolved;
    }

    private static function permalink(string $type, int $blogId, int $id): ?string
    {
        switch_to_blog($blogId);
        $url = match ($type) {
            'post' => get_post_status($id) === 'publish' ? get_permalink($id) : null,
            'category' => get_category_link($id) ?: null,
            default => null,
        };
        restore_current_blog();

        // Category links come back as "/blog/./slug/"; tidy the dot segment.
        return $url ? str_replace('/./', '/', $url) : null;
    }
}
