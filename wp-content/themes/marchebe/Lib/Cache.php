<?php

namespace AcMarche\Theme\Lib;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Cache
{
    private static ?CacheInterface $cache = null;
    private static ?SluggerInterface $slugger = null;

    private function __construct()
    {
    }

    public static function instance(): CacheInterface|FilesystemTagAwareAdapter
    {
        if (!self::$cache) {
            self::$cache = new FilesystemTagAwareAdapter('marcheWp', 60 * 60 * 8, self::getPathCache());
        }

        return self::$cache;
    }

    /**
     * Sous dossier de APP_CACHE_DIR: Twig ecrit ses templates compiles a la racine.
     */
    private static function getPathCache(): string
    {
        return ($_ENV['APP_CACHE_DIR'] ?? ABSPATH.'var/cache').'/pool';
    }

    public static function generateKey(string $cacheKey): string
    {
        if (!self::$slugger) {
            self::$slugger = new AsciiSlugger();
        }

        $keyUnicode = new UnicodeString($cacheKey);

        return self::$slugger->slug($keyUnicode->ascii()->toString());
    }

    // Helper method to get an item from cache
    public static function get(string $key, callable $callback, ?float $beta = null, ?array $tags = null)
    {
        $cacheKey = self::generateKey($key);

        //le 4e argument de CacheInterface::get() est $metadata, pas les tags: il faut taguer l'item
        return self::instance()->get($cacheKey, function (ItemInterface $item) use ($callback, $tags) {
            if ($tags) {
                $item->tag($tags);
            }

            return $callback($item);
        }, $beta);
    }

    // Helper method to delete an item from cache
    public static function delete(string $key): bool
    {
        $cacheKey = self::generateKey($key);

        return self::instance()->delete($cacheKey);
    }

    // Helper method to invalidate tags
    public static function invalidateTags(array $tags): bool
    {
        return self::instance()->invalidateTags($tags);
    }

    // Helper method to get an item from cache only if it exists (no computation)
    public static function getIfExists(string $key): mixed
    {
        $cacheKey = self::generateKey($key);
        $cache = self::instance();

        // Both ApcuAdapter and FilesystemAdapter implement CacheItemPoolInterface
        if ($cache instanceof CacheItemPoolInterface) {
            try {
                $item = $cache->getItem($cacheKey);
            } catch (InvalidArgumentException $e) {
                return null;
            }
            return $item->isHit() ? $item->get() : null;
        }

        return null;
    }
}
