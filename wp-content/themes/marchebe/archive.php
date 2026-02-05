<?php

namespace AcMarche\Theme\Templates;


use AcMarche\Theme\Lib\Helper\BreadcrumbHelper;
use AcMarche\Theme\Lib\Twig;
use AcMarche\Theme\Repository\WpRepository;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

get_header();

$cat_ID = get_queried_object_id();
$wpRepository = new WpRepository();
$children = $wpRepository->getChildrenOfCategory($cat_ID);
$category = get_category($cat_ID);
$category->url = get_category_link($cat_ID);
$description = category_description($cat_ID);
if($description) {
    $description = make_clickable($description);
}
$title = single_cat_title('', false);
$currentSite = get_current_blog_id();

$postsIndexed = [];
foreach ($wpRepository->getPostsAndFiches($cat_ID) as $post) {
    $postsIndexed[$post->id] = $post;
}
foreach ($children as $child) {
    foreach ($wpRepository->getPostsAndFiches($child->term_id) as $post) {
        $postsIndexed[$post->id] = $post;
    }
}
$posts = array_values($postsIndexed);

$twig = Twig::loadTwig();
$thumbnail = null;
$paths = BreadcrumbHelper::category($cat_ID);

try {
    echo $twig->render('@AcMarche/category/show.html.twig', [
        'category' => $category,
        'posts' => $posts,
        'postsSerialized' => json_encode($posts),
        'thumbnail' => $thumbnail,
        'thumbnail_srcset' => null,
        'thumbnail_sizes' => null,
        'paths' => $paths,
        'title' => $title,
        'description' => $description,
        'children' => $children,
        'currentSite' => $currentSite,
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    Twig::renderErrorPage($e);
}
get_footer();