<?php
/**
 * Utilise par https://api.marche.be/marchebe/actualites.
 * Diffusé sur odwb
 */

use AcMarche\Theme\Repository\WpRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

require_once(__DIR__.'/../wp-load.php');

$news = WpRepository::getNews(60);
$response = new JsonResponse($news);
$response->send();
