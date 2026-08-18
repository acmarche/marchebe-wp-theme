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
 * A window carries a colour picked by staff in the back office, stored as free
 * text. Only a hex colour is let through: the value ends up in a style
 * attribute, and anything else arriving there is a CSS injection rather than a
 * colour. A colour close to the board's paper is darkened until it separates
 * from it, since it was chosen against the white of an office screen.
 */
function officeAccent(?string $value): ?string
{
    if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', trim((string)$value)) !== 1) {
        return null;
    }

    $hex = ltrim(trim((string)$value), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }

    $channels = array_map(static fn(string $pair): int => (int)hexdec($pair), str_split($hex, 2));
    [$red, $green, $blue] = $channels;

    // sRGB luminance. The paper behind the frame sits at about 0.97, so a
    // colour much above half of that reads as another shade of the panel.
    $luminance = (0.2126 * $red + 0.7152 * $green + 0.0722 * $blue) / 255;
    if ($luminance > 0.5) {
        $channels = array_map(
                static fn(int $channel): int => (int)round($channel * 0.5 / $luminance),
                $channels,
        );
    }

    return sprintf('#%02x%02x%02x', ...$channels);
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
$ticketsByOffice = [];
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

    $offices = $pdo->query('SELECT id, name, color FROM offices ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

    $sql = <<<SQL
        SELECT t.id, t.number, t.service, t.office_id, t.createdAt, t.assigned_date
        FROM tickets t
        WHERE t.created_date = :today AND t.archive = 0 AND t.office_id IS NOT NULL
        SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['today' => $now->format('Y-m-d')]);
    $called = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // A call ranks by when it was assigned to a window, not by when the ticket
    // was printed. Most recent first, so a window's freshest call leads its
    // group on the board.
    $callTime = static fn(array $t): string => (string)($t['assigned_date'] ?? $t['createdAt']);
    usort($called, static fn(array $a, array $b): int => strcmp($callTime($b), $callTime($a)));

    // A window can hold several open tickets at once, so every call is kept
    // rather than only the latest one per window.
    foreach ($called as $ticket) {
        $ticketsByOffice[(int)$ticket['office_id']][] = $ticket;
    }
} catch (PDOException) {
    $hasError = true;
}

/*
 * One panel per call, so a window holding several tickets appears once per
 * ticket. Only windows with someone at them reach the board: a row of "Libre"
 * panels spends the brightest, largest real estate on the one thing nobody in
 * the queue is looking for. Office order is kept rather than call order, so a
 * panel does not jump sideways between two polls, and a window's own tickets
 * stay side by side under the same name.
 */
$callPanels = [];
foreach ($offices as $office) {
    $accent = officeAccent($office['color'] ?? null);
    foreach ($ticketsByOffice[(int)$office['id']] ?? [] as $ticket) {
        $callPanels[] = ['office' => $office, 'ticket' => $ticket, 'accent' => $accent];
    }
}

/*
 * Panels are laid out in balanced rows of at most four: four numbers is about
 * all the plaza scans at once, and splitting evenly keeps a second row from
 * trailing off with a single panel. The counts go to the stylesheet so the
 * panels divide the band exactly, whatever the day brings.
 */
$panelRows = max(1, (int)ceil(count($callPanels) / 4));
$panelColumns = max(1, (int)ceil(count($callPanels) / $panelRows));

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
                <ul class="counters__list" style="--cols: <?= $panelColumns ?>; --rows: <?= $panelRows ?>">
                    <?php foreach ($callPanels as $panel): ?>
                        <?php /* Keyed by ticket, not by window: a window can show several
                                 panels, so the ticket is what identifies a call to the
                                 script that flashes newly arrived ones. */ ?>
                        <li class="counter" data-call="<?= esc((string)$panel['ticket']['id']) ?>"<?php if ($panel['accent'] !== null): ?> style="--accent: <?= esc($panel['accent']) ?>"<?php endif; ?>>
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
    <link rel="stylesheet" href="/api/guichet/guichet.css?v=6">
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

        // The set of calls on the board, by ticket. A window can hold several
        // tickets and so show several panels, which rules out keying by window.
        function calledTickets(root) {
            const state = {};
            root.querySelectorAll('[data-call]').forEach(function (counter) {
                state[counter.getAttribute('data-call')] = true;
            });

            return state;
        }

        function flashNewCalls(before) {
            const current = board();
            current.querySelectorAll('[data-call]').forEach(function (counter) {
                if (before[counter.getAttribute('data-call')]) {
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
                    const before = calledTickets(board());
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
