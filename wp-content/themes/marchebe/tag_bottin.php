<?php

namespace AcMarche\Theme\Templates;

use AcMarche\Theme\Inc\RouterBottin;
use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Twig;
use AcMarche\Theme\Repository\BottinRepository;

get_header();

$slug = get_query_var(RouterBottin::PARAM_TAG, null);

$bottinRepository = new BottinRepository();
$tag = $bottinRepository->getTagBySlug($slug);

if (!$tag) {
    Twig::renderNotFoundPage('Tag non trouvé');
    wp_footer();

    return;
}

$fiches = $bottinRepository->getFichesByTag($tag->id);
array_map(
    function ($fiche) use ($bottinRepository) {
        $fiche->url = RouterBottin::getUrlFicheBottin(
            $bottinRepository->findByFicheIdWpSite($fiche),
            $fiche
        );
    },
    $fiches
);

$twig = Twig::loadTwig();

try {
    echo $twig->render('@AcMarche/bottin/list.html.twig', [
        'title' => $tag->name,
        'fiches' => $fiches,
        'site' => Theme::ECONOMIE,
    ]);
} catch (\Exception|\Throwable $e) {
    echo $e->getMessage();
}

get_footer();
