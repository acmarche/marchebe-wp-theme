<?php

namespace AcMarche\Theme\Templates;

use AcMarche\Theme\Inc\RouterBottin;
use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Twig;
use AcMarche\Theme\Repository\BottinRepository;

get_header();

$slug = get_query_var(RouterBottin::PARAM_CATEGORY, null);

$bottinRepository = new BottinRepository();
$category = $bottinRepository->getCategoryBySlug($slug);

if (!$category) {
    Twig::renderNotFoundPage('Catégorie non trouvée');
    wp_footer();

    return;
}

$fiches = $bottinRepository->getFichesByCategory($category->id);
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
        'title' => $category->name,
        'fiches' => $fiches,
        'site' => Theme::ECONOMIE,
    ]);
} catch (\Exception|\Throwable $e) {
    echo $e->getMessage();
}

get_footer();
