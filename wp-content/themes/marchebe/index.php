<?php

namespace AcMarche\Theme;

use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Twig;
use AcMarche\Theme\Repository\WpRepository;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

get_header();

$twig = Twig::loadTwig();
$wpRepository = new WpRepository();

$blog_id = get_current_blog_id();

$children = $wpRepository->getRootCategories();
$paths = [];
$path = Theme::getPathBlog($blog_id);
$title = Theme::getTitleBlog($blog_id);
$paths[] = ['name' => $title, 'term_id' => $blog_id, 'url' => $path];

try {
    echo $twig->render('@AcMarche/index.html.twig', [
        'thumbnail' => null,
        'thumbnail_srcset' => null,
        'thumbnail_sizes' => null,
        'paths' => $paths,
        'title' => $title,
        'children' => $children,
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}
get_footer();