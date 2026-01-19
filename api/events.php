<?php
/**
 * https://www.marche.be/api/events.php
 * Diffusé sur odwb
 */

use AcMarche\Theme\Lib\Pivot\Repository\PivotRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

require_once(__DIR__.'/../wp-load.php');
require_once '../vendor/autoload.php';

$pivotRepository = new PivotRepository();

try {
    $events = $pivotRepository->loadEvents();
} catch (\Exception|\Throwable  $e) {
    $events = [];
}

$response = new JsonResponse($events);
$response->send();