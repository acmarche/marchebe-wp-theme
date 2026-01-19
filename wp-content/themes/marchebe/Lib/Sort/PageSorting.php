<?php

namespace AcMarche\Theme\Lib\Sort;

use AcMarche\Theme\Lib\Twig;
use AcMarche\Theme\Repository\WpRepository;

class PageSorting
{
    public function __construct()
    {

    }

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
        add_submenu_page(
            'acmarche_trie',
            'Trie des articles',
            'Tri des articles',
            'edit_posts',
            'acmarche_trie_post',
            function () {
                PageSorting::renderPageCategory();
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

    static function renderPageCategory()
    {
        $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
        $category = get_category($cat_id);
        $wpRepository = new WpRepository();
        $posts = $wpRepository->getPostsAndFiches($cat_id);
        $posts = AcSort::getSortedItems($cat_id, $posts);

        Twig::rendPage(
            '@AcMarche/sort/tri_articles.html.twig',
            [
                'category' => $category,
                'posts' => $posts,
            ]
        );
    }
}
