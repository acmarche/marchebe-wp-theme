<?php
/**
 * Template Name: Event Single
 * Template for displaying external event content
 */

namespace AcMarche\Theme\Templates;

use AcMarche\Theme\Inc\RouterEnquete;
use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Pivot\Repository\PivotRepository;
use AcMarche\Theme\Lib\Search\Document;
use AcMarche\Theme\Lib\Twig;
use AcMarche\Theme\Repository\ApiRepository;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$enqueteId = get_query_var(RouterEnquete::PARAM_ENQUETE, null);

get_header();

if (!$enqueteId) {
    Twig::renderNotFoundPage('Code enquete invalide');
    get_footer();

    return;
}

$apiRepository = new ApiRepository();
try {
    $enquete = $apiRepository->getEnquetePublique($enqueteId);
} catch (\Exception $e) {
    Twig::renderErrorPage($e);
    get_footer();

    return;
}

$twig = Twig::loadTwig();
$category = $apiRepository->getCategoryEnquete();

$paths = $tags = [
    'name' => $category->name,
    'term_id' => $category->term_id,
    'link' => get_category_link($category),
];

$pivotRepository = new PivotRepository();
try {
    $events = $pivotRepository->loadEvents(skip: true);
    $events = array_slice($events, 0, 3);
} catch (\Exception|\Throwable  $e) {
    $events = [];
}

$document = Document::documentFromEnquete($enquete,'Enquêtes publiques');

try {
    echo $twig->render('@AcMarche/article/show.html.twig', [
        'post' => $enquete,
        'title' => $enquete->intitule,
        'body' => $document->content,
        'paths' => $paths,
        'site' => Theme::ADMINISTRATION,
        'tags' => $tags,
        'thumbnail' => null,
        'thumbnail_srcset' => null,
        'thumbnail_sizes' => null,
        'events' => $events,
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}

get_footer();