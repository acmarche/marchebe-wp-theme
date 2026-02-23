<?php

require_once __DIR__ . '/../../wp-load.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use AcMarche\Theme\Repository\ConseilRepository;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

$filesystem = new Filesystem();
$request = Request::createFromGlobals();
$fileName = $request->request->get('file_name');

if ($fileName) {
    try {
        $filesystem->remove(ConseilRepository::ORDRE_DIRECTORY.$fileName);
        echo json_encode(['result' => 'ok']);
    } catch (IOException $IOException) {
        echo json_encode(['error' => 'Impossible de supprimer le fichier '.$IOException->getMessage()], JSON_THROW_ON_ERROR);

        return;
    }
} else {
    echo json_encode(['error' => 'Nom de fichier obligatoire']);
}
