<?php

namespace AcMarche\Theme\Lib\Helper;

use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Repository\WpRepository;

class BreadcrumbHelper
{
    public static function currentPost(): array
    {
        $paths = [];
        $idSite = get_current_blog_id();

        if ($idSite > Theme::CITOYEN) {
            $path = Theme::getPathBlog($idSite);
            $blogName = Theme::getTitleBlog($idSite);
            $paths[] = ['name' => $blogName, 'term_id' => $idSite, 'link' => $path];
        }

        $catSlug = get_query_var('category_name');

        if (str_contains($catSlug, "/")) {
            $vars = explode("/", $catSlug);
            $catSlug = end($vars);
        }
        $currentCategory = get_category_by_slug($catSlug);
        if ($currentCategory) {
            $paths[] = [
                'name' => $currentCategory->name,
                'term_id' => $currentCategory->cat_ID,
                'link' => get_category_link($currentCategory),
            ];
            $categories = WpRepository::instance()->getAncestorsOfCategory($currentCategory->cat_ID);
            foreach ($categories as $category) {
                $paths[] = [
                    'name' => $category->name,
                    'term_id' => $category->cat_ID,
                    'link' => get_category_link($category),
                ];
            }
        }

        return $paths;
    }

    public static function category(int $categoryId): array
    {
        $idSite = get_current_blog_id();
        $wpRepository = new WpRepository();
        $paths = [];
        if ($idSite > Theme::CITOYEN) {
            $blogName = Theme::getTitleBlog($idSite);
            $paths[] = ['name' => $blogName, 'term_id' => $idSite, 'link' => Theme::getPathBlog($idSite)];
        }

        $parent = $wpRepository->getParentCategory($categoryId);
        if ($parent) {
            $paths[] = [
                'name' => $parent->name,
                'term_id' => $parent->cat_ID,
                'link' => get_category_link($parent->term_id),
            ];
        }

        return $paths;
    }
}