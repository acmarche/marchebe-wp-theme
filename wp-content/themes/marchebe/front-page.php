<?php

/**
 * Front page dispatcher.
 *
 * WordPress uses front-page.php for the front page of every site in the
 * multisite network. The main municipal site (CITOYEN) keeps its dedicated
 * homepage; every sub-site (sport, culture, tourisme, ...) gets a landing
 * page built from that blog's own "top-menu".
 */

namespace AcMarche\Theme\Templates;

use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Twig;
use AcMarche\Theme\Repository\MenuRepository;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$blog_id = get_current_blog_id();

// Main municipal site keeps its dedicated homepage.
if ($blog_id === Theme::CITOYEN) {
    require __DIR__.'/homepage.php';

    return;
}

get_header();

$page = get_post();

$image = null;
$image_srcset = null;
$image_sizes = null;
if (has_post_thumbnail()) {
    $attachment_id = get_post_thumbnail_id();
    $image = wp_get_attachment_image_url($attachment_id, 'hero-header');
    $image_srcset = wp_get_attachment_image_srcset($attachment_id, 'hero-header');
    $image_sizes = wp_get_attachment_image_sizes($attachment_id, 'hero-header');
}

$site = Theme::SITES[$blog_id] ?? null;
$menuItems = (new MenuRepository())->getItems($site);

$twig = Twig::loadTwig();
try {
    echo $twig->render('@AcMarche/page/front_site.html.twig', [
        'title' => $page ? $page->post_title : Theme::getTitleBlog($blog_id),
        'site' => $site,
        'menuItems' => $menuItems,
        'thumbnail' => $image,
        'thumbnail_srcset' => $image_srcset,
        'thumbnail_sizes' => $image_sizes,
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    Twig::renderErrorPage($e);
}

get_footer();
