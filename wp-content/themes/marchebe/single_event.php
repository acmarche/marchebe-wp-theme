<?php
/**
 * Template Name: Event Single
 * Template for displaying external event content
 */

namespace AcMarche\Theme\Templates;

use AcMarche\Theme\Inc\RouterEvent;
use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Pivot\Repository\PivotRepository;
use AcMarche\Theme\Lib\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$codeCgt = get_query_var(RouterEvent::PARAM_EVENT);

get_header();

if (!str_contains($codeCgt, "EVT")) {
    Twig::renderNotFoundPage('Code CGT invalide');
    get_footer();

    return;
}

$pivotRepository = new PivotRepository();
try {
    $event = $pivotRepository->loadOneEvent($codeCgt, parse: true, purgeCache: WP_DEBUG);
} catch (\JsonException $e) {
    Twig::renderErrorPage($e);
    get_footer();

    return;
}

$twig = Twig::loadTwig();

if (!$event) {
    Twig::renderNotFoundPage('Evènement non trouvé');
    get_footer();

    return;
}

if (count($event->dates) === 0) {
    Twig::renderNotFoundPage('Evènement cloturé');
    get_footer();

    return;
}

$image = count(
    $event->images
) > 0 ? $event->images[0] : 'https://pivotmedia.tourismewallonie.be/OTH-A0-00UE-0HH1/OTH-A0-00UE-0HH1.jpg';

$paths = $tags = [
    'name' => 'Agenda des manifestations',
    'term_id' => 5,
    'link' => '/tourisme/agenda-des-manifestations',
];

try {
    echo $twig->render('@AcMarche/agenda/show.html.twig', [
        'event' => $event,
        'title' => $event->nom,
        'paths' => [$paths],
        'site' => Theme::TOURISME,
        'tags' => $tags,
        'images' => $event->images,
        'thumbnail' => $image,
        'thumbnail_srcset' => null,
        'thumbnail_sizes' => null,
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}

get_footer();