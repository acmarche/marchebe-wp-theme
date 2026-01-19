<?php

namespace AcMarche\Theme\Lib\Sort;

use AcMarche\Theme\Lib\Twig;
use AcMarche\Theme\Repository\WpRepository;

class PageSorting
{
    static function loadPages()
    {
        $position = 61;
        add_menu_page(
            'Tri des articles',
            'Tri',
            'edit_posts',
            'acmarche_trie',
            function () {
                PageSorting::pageIndex();
            },
            'dashicons-sort',
            $position
        );
        add_submenu_page(
            'acmarche_trie',
            'Trie des news',
            'Tri des news',
            'edit_posts',
            'ac_marche_tri_news',
            function () {
                PageSorting::renderPageNews();
            },
        );
    }

    static function pageIndex()
    {
        $urlNews = admin_url('/admin.php?page=ac_marche_tri_news');
        Twig::rendPage(
            '@AcMarche/sort/menu.html.twig',
            [
                'urlNews' => $urlNews,
            ]
        );
    }

    static function renderPageNews()
    {
        $wpRepository = new WpRepository();
        $news = $wpRepository->getNews(30);

        Twig::rendPage(
            '@AcMarche/sort/tri_news.html.twig',
            [
                'news' => $news,
            ]
        );
    }
}
