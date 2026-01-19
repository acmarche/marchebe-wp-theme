<?php

namespace AcMarche\Theme\Repository;

use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Cache;

class MenuRepository
{
    const MENU_NAME = 'top-menu';

    public function getMenu(bool $purgeCache = false): array
    {
        $cacheKey = Cache::generateKey('menu-top');

        if ($purgeCache) {
            Cache::delete($cacheKey);
        }

        return Cache::get($cacheKey, function (): array {
            $blog = get_current_blog_id();
            $data = [];
            foreach (Theme::SITES as $idSite => $site) {
                switch_to_blog($idSite);
                if (in_array($idSite, [8, 12])) {
                    continue;
                }
                $data[$idSite]['name'] = ucfirst($site);
                $data[$idSite]['slug'] = $site;
                if ($idSite == 14) {
                    $data[$idSite]['name'] = 'Enfance-Jeunesse';
                }
                $data[$idSite]['blogid'] = $idSite;
                $data[$idSite]['colorhover'] = 'hover:text-'.$site;
                $data[$idSite]['color'] = 'text-'.$site;
                $data[$idSite]['items'] = $this->getItems($site);
            }
            switch_to_blog($blog);

            return $this->sortByName($data);
        }
        );
    }

    public function getItems(string $site = null): array
    {
        $menu = wp_get_nav_menu_object(self::MENU_NAME);

        $args = array(
            'order' => 'ASC',
            'orderby' => 'menu_order',
            'post_type' => 'nav_menu_item',
            'post_status' => 'publish',
            'output' => ARRAY_A,
            'output_key' => 'menu_order',
            'nopaging' => true,
            'update_post_term_cache' => false,
        );

        $data = wp_get_nav_menu_items($menu, $args);
        foreach ($data as $row) {
            $row->blog = $site;
            $row->id = (int)$row->object_id;
            if ($row->object === 'post') {
                $post = get_post($row->object_id);
                if (!$post) {
                    continue;
                }
                $row->slug = $post->post_name;
            }
            if ($row->object === 'page') {
                $page = get_post($row->object_id);
                if (!$page) {
                    continue;
                }
                $row->slug = $page->post_name;
            }
            if ($row->object === 'category') {
                $category = get_category($row->object_id);
                if ($category) {
                    $row->slug = $category->slug;
                }
            }
            if ($row->object === 'custom') {
                $row->slug = $row->post_name;
            }
        }

        return $data;
    }

    public function sortByName(array $data): array
    {
        usort(
            $data,
            function ($itemA, $itemB) {
                $nameA = $itemA['name'];
                $nameB = $itemB['name'];

                return $nameA > $nameB ? +1 : -1;
            }
        );

        return $data;
    }


}