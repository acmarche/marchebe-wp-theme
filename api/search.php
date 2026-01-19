<?php
/**
 * use for inline search in header
 */
use AcMarche\Theme\Lib\Mailer;
use AcMarche\Theme\Lib\Search\MeiliSearch;
use Symfony\Component\HttpFoundation\JsonResponse;

require_once(__DIR__.'/../wp-load.php');
$searcher = new MeiliSearch();
$searcher->initClientAndIndex();
$keyword = $_GET['s'] ?? null;
if (!$keyword) {
    $response = new JsonResponse(['error' => 'no keyword'], 500);
    $response->send();
}
try {
    $searching = $searcher->doSearch($keyword);
    $hits = $searching->getHits();
    $count = $searching->count();
    $response = new JsonResponse($hits);
    $response->send();
} catch (Exception $e) {
    Mailer::sendError("wp error search", $e->getMessage());
    $hits = ['error' => $e->getMessage()];
    $response = new JsonResponse($hits, 500);
    $response->send();
}
