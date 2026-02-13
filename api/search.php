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
$keyword = trim((string)($_GET['s'] ?? ''));
if ($keyword === '' || mb_strlen($keyword) > 200) {
    $response = new JsonResponse(['error' => 'invalid keyword'], 400);
    $response->send();
    exit;
}
try {
    $searching = $searcher->doSearch($keyword);
    $hits = $searching->getHits();
    $response = new JsonResponse($hits);
    $response->send();
} catch (Exception $e) {
    $response = new JsonResponse(['error' => 'search failed'], 500);
    $response->send();
}