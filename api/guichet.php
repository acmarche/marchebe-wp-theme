<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(dirname(__DIR__).'/.env');

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
WHERE t.created_date = :today
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
    <script src="https://cdn.tailwindcss.com"></script>
    <meta http-equiv="refresh" content="15">
</head>
<body class="bg-gray-900 text-white min-h-screen">

<div class="p-8">
    <header class="text-center mb-10">
        <h1 class="text-5xl font-bold tracking-tight">Guichet - File d'attente</h1>
        <p class="text-2xl text-gray-400 mt-2"><?= date('d/m/Y - H:i') ?></p>
    </header>

    <?php if (!empty($called)): ?>
    <section class="mb-12">
        <h2 class="text-3xl font-semibold text-center mb-6 text-green-400">Tickets appelés</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 max-w-7xl mx-auto">
            <?php foreach ($called as $ticket): ?>
            <div class="bg-green-800/40 border-2 border-green-500 rounded-2xl p-8 text-center">
                <div class="text-7xl font-black mb-4"><?= htmlspecialchars($ticket['number']) ?></div>
                <div class="text-3xl font-bold text-green-300"><?= htmlspecialchars($ticket['office_name']) ?></div>
                <div class="text-xl text-gray-300 mt-2"><?= htmlspecialchars($ticket['service']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($waiting)): ?>
    <section>
        <h2 class="text-3xl font-semibold text-center mb-6 text-yellow-400">En attente</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 max-w-7xl mx-auto">
            <?php foreach ($waiting as $ticket): ?>
            <div class="bg-yellow-800/30 border border-yellow-600 rounded-xl p-6 text-center">
                <div class="text-5xl font-black mb-2"><?= htmlspecialchars($ticket['number']) ?></div>
                <div class="text-2xl text-yellow-300">Veuillez patienter</div>
                <div class="text-lg text-gray-400 mt-1"><?= htmlspecialchars($ticket['service']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (empty($called) && empty($waiting)): ?>
    <div class="text-center text-4xl text-gray-500 mt-32">Aucun ticket pour aujourd'hui</div>
    <?php endif; ?>
</div>

</body>
</html>
