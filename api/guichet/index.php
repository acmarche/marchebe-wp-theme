<?php

require_once dirname(__DIR__).'/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(dirname(__DIR__).'/../.env');

$dsn = 'mysql:host=127.0.0.1;dbname=guichet';
$username = $_ENV['DB_GUICHET_USER'];
$password = $_ENV['DB_GUICHET_PASS'];

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Erreur de connexion à la base de données';
    exit;
}

$today = date('Y-m-d');

$sql = <<<SQL
SELECT t.id, t.number, t.reason, t.service, t.office_id, t.createdAt, t.archive,
       o.name AS office_name
FROM ticket t
LEFT JOIN office o ON t.office_id = o.id
WHERE t.created_date = :today AND t.archive = 0
ORDER BY t.createdAt DESC
SQL;

$stmt = $pdo->prepare($sql);
$stmt->execute(['today' => $today]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Split: currently called (has office, not archived) vs waiting (no office)
$called = [];
$waiting = [];
foreach ($tickets as $ticket) {
    if ($ticket['office_id'] !== null) {
        $called[] = $ticket;
    } else {
        $waiting[] = $ticket;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guichet - File d'attente</title>
    <link rel="stylesheet" href="guichet.css">
    <meta http-equiv="refresh" content="15">
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Guichet - File d'attente</h1>
        <p class="date"><?= date('d/m/Y - H:i') ?></p>
    </div>

    <?php if (!empty($called)): ?>
    <div class="section">
        <h2 class="section-title section-title-green">Tickets appel&eacute;s</h2>
        <div class="grid">
            <?php foreach ($called as $ticket): ?>
            <div class="card-called">
                <div class="number"><?= htmlspecialchars($ticket['number']) ?></div>
                <div class="office"><?= htmlspecialchars($ticket['office_name']) ?></div>
                <div class="service"><?= htmlspecialchars($ticket['service']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($waiting)): ?>
    <div class="section">
        <h2 class="section-title section-title-yellow">En attente</h2>
        <div class="grid">
            <?php foreach ($waiting as $ticket): ?>
            <div class="card-waiting">
                <div class="number"><?= htmlspecialchars($ticket['number']) ?></div>
                <div class="status">Veuillez patienter</div>
                <div class="service"><?= htmlspecialchars($ticket['service']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($called) && empty($waiting)): ?>
    <div class="empty">Aucun ticket pour aujourd'hui</div>
    <?php endif; ?>
</div>

</body>
</html>
