<?php

namespace AcMarche\Theme\Templates;

use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Helper\BreadcrumbHelper;
use AcMarche\Theme\Lib\Pivot\Repository\PivotRepository;
use AcMarche\Theme\Lib\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

get_header();

$post = get_post();

$twig = Twig::loadTwig();

$image = null;
$image_srcset = null;
$image_sizes = null;
if (has_post_thumbnail()) {
    $attachment_id = get_post_thumbnail_id();
    $image = wp_get_attachment_image_url($attachment_id, 'hero-header');
    $image_srcset = wp_get_attachment_image_srcset($attachment_id, 'hero-header');
    $image_sizes = wp_get_attachment_image_sizes($attachment_id, 'hero-header');
}

$tags = [];
foreach (get_the_category($post->ID) as $category) {
    $tags[] = ['id' => $category->term_id, 'name' => $category->name, 'url' => get_category_link($category->term_id)];
}
$paths = BreadcrumbHelper::currentPost();

$content = get_the_content(null, null, $post);
$content = apply_filters('the_content', $content);
$content = str_replace(']]>', ']]&gt;', $content);

$pivotRepository = new PivotRepository();
try {
    $events = $pivotRepository->loadEvents(skip:true);
    $events = array_slice($events, 0, 3);
} catch (\Exception|\Throwable  $e) {
    $events = [];
}

try {
    echo $twig->render('@AcMarche/article/show.html.twig', [
        'post' => $post,
        'title' => $post->post_title,
        'body' => $content,
        'paths' => $paths,
        'site' => Theme::TOURISME,
        'tags' => $tags,
        'thumbnail' => $image,
        'thumbnail_srcset' => $image_srcset,
        'thumbnail_sizes' => $image_sizes,
        'events' => $events,
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    Twig::renderErrorPage($e);
}

get_footer();