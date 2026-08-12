<?php

declare(strict_types=1);

/**
 * Public queue board for the Marche-en-Famenne administration hall.
 *
 * Rendered on a 16:9 screen mounted above the entrance door, read from the
 * plaza at 3-10 m and from below. Every decision here serves that: a bright
 * surface that beats the specular reflection on the panel, very large type,
 * and a fixed layout that stays learnable all day.
 *
 * `?partial=1` returns the board fragment alone, which the page polls so the
 * screen updates without the black flash of a full reload.
 */

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->load(dirname(__DIR__, 2).'/.env');

$isPartial = isset($_GET['partial']);

/*
 * Marks the board as a test display. Driven only by `GUICHET_TEST_MODE=1` in
 * `.env`, never by a query parameter: the board is served over HTTP, and a
 * toggle in the URL would let anyone make the real board look untrustworthy.
 */
$isTest = filter_var($_ENV['GUICHET_TEST_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function esc(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * French long date, without relying on ext-intl being present on the kiosk host.
 */
function frenchDate(DateTimeImmutable $date): string
{
    $days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $months = [
            1 => 'janvier',
            'février',
            'mars',
            'avril',
            'mai',
            'juin',
            'juillet',
            'août',
            'septembre',
            'octobre',
            'novembre',
            'décembre',
    ];

    return sprintf(
            '%s %d %s %s',
            $days[(int)$date->format('w')],
            (int)$date->format('j'),
            $months[(int)$date->format('n')],
            $date->format('Y'),
    );
}

$now = new DateTimeImmutable();

$offices = [];
$currentByOffice = [];
$hasError = false;

try {
    $pdo = new PDO(
            'mysql:host=127.0.0.1;dbname=guichet',
            $_ENV['DB_GUICHET_USER'] ?? '',
            $_ENV['DB_GUICHET_PASS'] ?? '',
            [
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ],
    );

    $offices = $pdo->query('SELECT id, name FROM offices ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

    $sql = <<<SQL
        SELECT t.id, t.number, t.service, t.office_id, t.createdAt, t.assigned_date
        FROM tickets t
        WHERE t.created_date = :today AND t.archive = 0 AND t.office_id IS NOT NULL
        SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['today' => $now->format('Y-m-d')]);
    $called = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // A call ranks by when it was assigned to a window, not by when the ticket
    // was printed. Most recent first, so the first call seen for a window is
    // the one it is serving now.
    $callTime = static fn(array $t): string => (string)($t['assigned_date'] ?? $t['createdAt']);
    usort($called, static fn(array $a, array $b): int => strcmp($callTime($b), $callTime($a)));

    foreach ($called as $ticket) {
        $officeId = (int)$ticket['office_id'];
        if (!isset($currentByOffice[$officeId])) {
            $currentByOffice[$officeId] = $ticket;
        }
    }
} catch (PDOException) {
    $hasError = true;
}

/*
 * Only windows with someone at them reach the board. A row of "Libre" panels
 * spends the brightest, largest real estate on the one thing nobody in the
 * queue is looking for. Office order is kept rather than call order, so a panel
 * does not jump sideways between two polls.
 */
$callPanels = [];
foreach ($offices as $office) {
    $ticket = $currentByOffice[(int)$office['id']] ?? null;
    if ($ticket !== null) {
        $callPanels[] = ['office' => $office, 'ticket' => $ticket];
    }
}

ob_start();
?>
<main class="board<?= $isTest ? ' board--test' : '' ?>" id="board">

    <?php if ($isTest): ?>
        <?php /* A row of the board rather than a corner of it. The rotated corner
                 ribbon sat on top of the first window's name, and reading the
                 window names is one of the things a trial is there to check. */ ?>
        <div class="ribbon">
            <p class="ribbon__label">
                <strong class="ribbon__strong">Phase de test</strong>&nbsp;:
                Nous sommes en cours de test
            </p>
        </div>
    <?php endif; ?>

    <header class="rail">
        <div class="rail__identity">
            <p class="rail__town">Ville de Marche-en-Famenne</p>
            <h1 class="rail__title">File d'attente</h1>
        </div>
        <div class="rail__time">
            <p class="rail__date"><?= esc(frenchDate($now)) ?></p>
            <p class="rail__clock" id="clock"><?= esc($now->format('H:i')) ?></p>
        </div>
    </header>

    <?php if ($hasError): ?>

        <section class="notice" aria-labelledby="notice-title">
            <h2 class="notice__title" id="notice-title">Affichage momentanément indisponible</h2>
            <p class="notice__body">Adressez-vous à l'accueil pour connaître votre tour.</p>
        </section>

    <?php else: ?>

        <section class="counters" aria-labelledby="counters-title" aria-live="polite">
            <?php /* The band needs no visible label: four huge numbers under a
                     window name are self-evident from the plaza. The heading
                     stays in the DOM so the page keeps a readable outline. */ ?>
            <h2 class="visually-hidden" id="counters-title">Numéros appelés</h2>

            <?php if ($callPanels === []): ?>
                <div class="section-empty">
                    <?php if ($offices === []): ?>
                        <p class="section-empty__lead">Aucun guichet configuré.</p>
                    <?php else: ?>
                        <p class="section-empty__lead">Aucun numéro appelé pour le moment.</p>
                        <p class="section-empty__hint">Le numéro s'affiche ici dès qu'un guichet vous appelle.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <ul class="counters__list">
                    <?php foreach ($callPanels as $panel): ?>
                        <li class="counter"
                            data-office="<?= esc((string)$panel['office']['id']) ?>"
                            data-ticket="<?= esc($panel['ticket']['number']) ?>">
                            <?php /* Three fixed rows in every panel, the service one empty where
                                     the ticket carries no service, so numbers keep a shared
                                     baseline across the band. */ ?>
                            <p class="counter__name"><?= esc($panel['office']['name']) ?></p>
                            <p class="counter__number"><?= esc($panel['ticket']['number']) ?></p>
                            <p class="counter__service"><?= esc($panel['ticket']['service']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

    <?php endif; ?>

    <p class="offline" role="status">Affichage hors ligne, les numéros peuvent avoir changé.</p>

</main>
<?php
$boardMarkup = ob_get_clean();

if ($isPartial) {
    echo $boardMarkup;

    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isTest ? '[Test] ' : '' ?>File d'attente - Ville de Marche-en-Famenne</title>
    <link rel="stylesheet" href="/api/guichet/guichet.css?v=4">
    <noscript>
        <meta http-equiv="refresh" content="20">
    </noscript>
</head>
<body>

<?= $boardMarkup ?>

<script>
    (function () {
        'use strict';

        const POLL_MS = 10000;
        const RELOAD_MS = 3600000;
        const FAILURES_BEFORE_OFFLINE = 3;

        let failures = 0;

        function board() {
            return document.getElementById('board');
        }

        function calledNumbers(root) {
            const state = {};
            root.querySelectorAll('[data-office]').forEach(function (counter) {
                state[counter.getAttribute('data-office')] = counter.getAttribute('data-ticket');
            });

            return state;
        }

        function flashNewCalls(before) {
            const current = board();
            const after = calledNumbers(current);
            Object.keys(after).forEach(function (office) {
                if (!after[office] || after[office] === before[office]) {
                    return;
                }
                const counter = current.querySelector('[data-office="' + office + '"]');
                if (!counter) {
                    return;
                }
                // Settle the freshly inserted panel's style first. Without this
                // the class lands in the same recalculation as the insertion,
                // so there is no change for the animation to start from.
                void counter.offsetWidth;
                counter.classList.add('is-new');
                const clear = function () {
                    counter.classList.remove('is-new');
                };
                counter.addEventListener('animationend', clear, {once: true});
                // Reduced motion removes the animation, so animationend never
                // arrives and the class would otherwise stick.
                setTimeout(clear, 1500);
            });
        }

        function poll() {
            fetch('?partial=1&t=' + Date.now(), {cache: 'no-store'})
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error(String(response.status));
                    }

                    return response.text();
                })
                .then(function (html) {
                    const next = new DOMParser().parseFromString(html, 'text/html').getElementById('board');
                    if (!next) {
                        throw new Error('missing board');
                    }
                    const before = calledNumbers(board());
                    board().replaceWith(next);
                    flashNewCalls(before);
                    failures = 0;
                    document.body.classList.remove('is-offline');
                })
                .catch(function () {
                    failures += 1;
                    if (failures >= FAILURES_BEFORE_OFFLINE) {
                        document.body.classList.add('is-offline');
                    }
                });
        }

        function tickClock() {
            const clock = document.getElementById('clock');
            if (!clock) {
                return;
            }
            const now = new Date();
            clock.textContent = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        }

        setInterval(poll, POLL_MS);
        setInterval(tickClock, 15000);

        // The board runs unattended for weeks. A scheduled full reload keeps a
        // long-lived page from drifting on memory or a stale stylesheet.
        setTimeout(function () {
            window.location.reload();
        }, RELOAD_MS);
    }());
</script>

</body>
</html>
