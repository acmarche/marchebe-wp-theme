<?php

/**
 * Template Name: Home-Page-Principal
 */

namespace AcMarche\Theme\Templates;

use AcMarche\Theme\Data\Data;
use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Mailer;
use AcMarche\Theme\Lib\Pivot\Repository\PivotRepository;
use AcMarche\Theme\Lib\Sort\SortLink;
use AcMarche\Theme\Lib\Twig;
use AcMarche\Theme\Repository\WpRepository;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

get_header();

$wpRepository = new WpRepository();
$pivotRepository = new PivotRepository();
try {
    $events = $pivotRepository->loadEvents(purgeCache: WP_DEBUG, skip: true);
    foreach ($events as $key => $event) {
        if (in_array($event->codeCgt, Data::$eventsToSkip)) {
            unset($events[$key]);
        }
    }
} catch (\JsonException|\Throwable $e) {
    $events = [];
    Mailer::sendError("pivot Error cache full json", $e->getMessage());
}

$events = array_slice($events, 0, 8);
$news = $wpRepository->getNews();
$news = array_slice($news, 0, 6);

$alertPage = get_post(Theme::PAGE_ALERT);
if ($alertPage) {
    $alertPage->url = get_permalink($alertPage);
}

$twig = Twig::loadTwig();
try {
    echo $twig->render('@AcMarche/homepage.html.twig', [
        'events' => $events,
        'news' => $news,
        'shortcuts' => Data::shortcuts,
        'widgets' => Data::widgets,
        'partners' => Data::partners,
        'alertPage' => $alertPage,
        'sortLink' => SortLink::linkSortNews(),
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}

get_footer();