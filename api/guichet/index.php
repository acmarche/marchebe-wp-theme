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

/** Waiting numbers the board shows before collapsing the rest into a counter. */
const WAITING_SLOTS = 12;

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
$waiting = [];
$currentByOffice = [];
$earlierCalls = [];
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
        SELECT t.id, t.number, t.service, t.office_id, t.createdAt, t.assigned_date,
               o.name AS office_name
        FROM tickets t
        LEFT JOIN offices o ON t.office_id = o.id
        WHERE t.created_date = :today AND t.archive = 0
        SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['today' => $now->format('Y-m-d')]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $called = [];
    foreach ($tickets as $ticket) {
        if ($ticket['office_id'] === null) {
            $waiting[] = $ticket;
        } else {
            $called[] = $ticket;
        }
    }

    // The queue reads in the order people will be served: oldest ticket first.
    usort($waiting, static fn(array $a, array $b): int => strcmp((string)$a['createdAt'], (string)$b['createdAt']));

    // A call ranks by when it was assigned to a window, not by when the ticket
    // was printed.
    $callTime = static fn(array $t): string => (string)($t['assigned_date'] ?? $t['createdAt']);
    usort($called, static fn(array $a, array $b): int => strcmp($callTime($b), $callTime($a)));

    foreach ($called as $ticket) {
        $officeId = (int)$ticket['office_id'];
        if (isset($currentByOffice[$officeId])) {
            $earlierCalls[] = $ticket;
        } else {
            $currentByOffice[$officeId] = $ticket;
        }
    }
} catch (PDOException) {
    $hasError = true;
}

$waitingTotal = count($waiting);
$waitingOverflow = max(0, $waitingTotal - WAITING_SLOTS);
$waitingShown = $waitingOverflow > 0
        ? array_slice($waiting, 0, WAITING_SLOTS - 1)
        : $waiting;

ob_start();
?>
<main class="board<?= $isTest ? ' board--test' : '' ?>" id="board">

    <?php if ($isTest): ?>
        <div class="ribbon">
            <p class="ribbon__label">Test</p>
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
            <h2 class="section-title" id="counters-title">Appelés</h2>

            <?php if ($offices === []): ?>
                <p class="section-empty__lead">Aucun guichet configuré.</p>
            <?php else: ?>
                <ul class="counters__list">
                    <?php foreach ($offices as $office): ?>
                        <?php
                        $ticket = $currentByOffice[(int)$office['id']] ?? null;
                        ?>
                        <li class="counter<?= $ticket === null ? ' counter--idle' : '' ?>"
                            data-office="<?= esc((string)$office['id']) ?>"
                            data-ticket="<?= esc($ticket['number'] ?? '') ?>">
                            <?php /* Every panel renders the same three rows, empty where it has
                                     nothing to say, so numbers align across the whole band. */ ?>
                            <p class="counter__name"><?= esc($office['name']) ?></p>
                            <?php if ($ticket === null): ?>
                                <p class="counter__number counter__number--idle">Libre</p>
                                <p class="counter__service"></p>
                            <?php else: ?>
                                <p class="counter__number"><?= esc($ticket['number']) ?></p>
                                <p class="counter__service"><?= esc($ticket['service']) ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="waiting" aria-labelledby="waiting-title">
            <h2 class="section-title" id="waiting-title">
                En attente
                <?php if ($waitingTotal > 0): ?>
                    <span class="section-title__count"><?= $waitingTotal ?></span>
                <?php endif; ?>
            </h2>

            <?php if ($waitingShown === []): ?>
                <div class="section-empty">
                    <p class="section-empty__lead">Personne n'attend pour le moment.</p>
                    <p class="section-empty__hint">Prenez votre ticket à l'accueil.</p>
                </div>
            <?php else: ?>
                <ul class="waiting__list">
                    <?php foreach ($waitingShown as $index => $ticket): ?>
                        <li class="ticket<?= $index === 0 ? ' ticket--next' : '' ?>">
                            <p class="ticket__number"><?= esc($ticket['number']) ?></p>
                            <p class="ticket__service"><?= esc($ticket['service']) ?></p>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($waitingOverflow > 0): ?>
                        <li class="ticket ticket--overflow">
                            <p class="ticket__number">+<?= $waitingOverflow + 1 ?></p>
                            <p class="ticket__service">autres</p>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php if ($earlierCalls !== []): ?>
            <section class="history" aria-labelledby="history-title">
                <h2 class="history__title" id="history-title">Déjà appelés aujourd'hui</h2>
                <ul class="history__list">
                    <?php foreach ($earlierCalls as $ticket): ?>
                        <li class="history__item">
                            <?= esc($ticket['number']) ?>
                            <span class="history__office"><?= esc($ticket['office_name']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

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
    <link rel="stylesheet" href="/api/guichet/guichet.css?v=2">
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
