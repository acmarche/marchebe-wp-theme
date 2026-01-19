<?php

/**
 * Template Name: News
 */

namespace AcMarche\Theme;

use AcMarche\Theme\Repository\WpRepository;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use AcMarche\Theme\Lib\Twig;

get_header();

$twig = Twig::loadTwig();
$category = get_category(11);
$wpRepository = new WpRepository();
$news = $wpRepository->getNews();
$paths = [];

try {
    echo $twig->render('@AcMarche/news/index.html.twig', [
        'news' => $news,
        'thumbnail' => null,
        'thumbnail_srcset' => null,
        'thumbnail_sizes' => null,
        'paths' => $paths,
        'tags' => [],
        'title' => $category->name,
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}
get_footer();
