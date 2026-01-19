<?php


namespace AcMarche\Theme\Lib\Sort;

use AcMarche\Theme\Lib\Twig;

class SortLink
{
    public static function linkSortNews(): ?string
    {
        if (current_user_can('edit_posts')) {
            $url = admin_url('/admin.php?page=ac_marche_tri_news');
            $twig = Twig::LoadTwig();

            return $twig->render('@AcMarche/sort/_link_tri_news.html.twig', ['url' => $url]);
        }

        return null;
    }
}
