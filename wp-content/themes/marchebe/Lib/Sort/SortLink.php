<?php


namespace AcMarche\Theme\Lib\Sort;

use AcMarche\Theme\Lib\Twig;

class SortLink
{
    static function linkSortNews(): ?string
    {
        if (current_user_can('edit_posts')) {
            $url = admin_url('/admin.php?page=ac_marche_tri_news');
            $twig = Twig::LoadTwig();

            return $twig->render('admin/_link_tri_news.html.twig', ['url' => $url]);
        }

        return null;
    }

    static function linkSortArticles($cat_ID): ?string
    {
        $category_order = get_term_meta($cat_ID, 'acmarche_category_sort', true);

        if ($category_order == 'manual') {
            if (current_user_can('edit_posts')) {
                $url = admin_url('admin.php?page=acmarche_trie_post&cat_id='.$cat_ID);
                $twig = Twig::LoadTwig();

                return $twig->render('admin/_link_tri_article.html.twig', ['url' => $url]);
            }
        }

        return null;
    }
}
