<?php

declare(strict_types=1);

/**
 * Autoplay probe for the queue board's chime.
 *
 * The board rings `ticket-assigned.mp3` when a panel appears, with no user
 * gesture behind it: the screen is bolted above the door and nobody ever
 * touches it. That only works when the browser is started with
 * `--autoplay-policy=no-user-gesture-required`, so this page answers the one
 * question worth asking on the kiosk itself — does sound come out before
 * anyone touches anything?
 *
 * Load it on the kiosk and read the verdict without clicking: a click would
 * grant the very permission being tested. The manual button below is for the
 * separate question of whether the file and the speakers work at all.
 */

$soundUrl = '/api/guichet/Soft-electronic-music-instrumental.mp3';
$soundFile = __DIR__.'/Soft-electronic-music-instrumental.mp3';
$soundExists = is_readable($soundFile);
$soundSize = $soundExists ? filesize($soundFile) : 0;

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test du son &mdash; guichet</title>
    <style>
        :root {
            color-scheme: light;
        }

        body {
            margin: 0;
            padding: 2rem;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: 20px;
            line-height: 1.5;
            background: #fff;
            color: #1a1a1a;
        }

        h1 {
            font-size: 2rem;
            margin: 0 0 1.5rem;
        }

        #verdict {
            padding: 1.25rem 1.5rem;
            border-radius: .5rem;
            border: 4px solid #999;
            background: #f4f4f4;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        #verdict.ok {
            border-color: #1a7f37;
            background: #e8f5eb;
            color: #10451d;
        }

        #verdict.blocked {
            border-color: #b91c1c;
            background: #fdeaea;
            color: #7a1212;
        }

        #verdict small {
            display: block;
            font-size: 1rem;
            font-weight: 400;
            margin-top: .5rem;
        }

        button {
            font: inherit;
            font-size: 1.2rem;
            padding: .75rem 1.5rem;
            border: 2px solid #1a1a1a;
            border-radius: .375rem;
            background: #1a1a1a;
            color: #fff;
            cursor: pointer;
        }

        button:hover {
            background: #333;
        }

        dl {
            margin: 2rem 0 0;
            display: grid;
            grid-template-columns: max-content 1fr;
            gap: .5rem 1.5rem;
            font-size: 1rem;
        }

        dt {
            font-weight: 700;
        }

        dd {
            margin: 0;
            font-family: ui-monospace, monospace;
            word-break: break-all;
        }

        .missing {
            color: #b91c1c;
            font-weight: 700;
        }

        #log {
            margin-top: 2rem;
            font-family: ui-monospace, monospace;
            font-size: .9rem;
            white-space: pre-wrap;
            color: #555;
        }
    </style>
</head>
<body>

<h1>Test du son du guichet</h1>

<?php if (!$soundExists) { ?>
    <p class="missing">Fichier introuvable&nbsp;: <?= htmlspecialchars($soundFile, ENT_QUOTES) ?></p>
<?php } ?>

<div id="verdict">Lecture automatique en cours&hellip;<small>Ne cliquez pas&nbsp;: un clic autoriserait justement ce qui est test&eacute; ici.</small></div>

<button type="button" id="manual">Jouer le son manuellement</button>

<dl>
    <dt>Fichier</dt>
    <dd><?= htmlspecialchars($soundUrl, ENT_QUOTES) ?></dd>
    <dt>Taille</dt>
    <dd><?= $soundExists ? number_format($soundSize).' o' : '&mdash;' ?></dd>
    <dt>Dur&eacute;e</dt>
    <dd id="duration">&mdash;</dd>
    <dt>Navigateur</dt>
    <dd id="ua">&mdash;</dd>
</dl>

<div id="log"></div>

<script>
    (function () {
        'use strict';

        const CALL_SOUND_URL = <?= json_encode($soundUrl) ?>;

        const verdict = document.getElementById('verdict');
        const log = document.getElementById('log');
        const sound = new Audio(CALL_SOUND_URL);

        document.getElementById('ua').textContent = navigator.userAgent;

        function write(line) {
            log.textContent += line + '\n';
        }

        function say(text, hint, state) {
            verdict.className = state;
            verdict.textContent = text;
            if (hint) {
                const small = document.createElement('small');
                small.textContent = hint;
                verdict.appendChild(small);
            }
        }

        sound.addEventListener('loadedmetadata', function () {
            document.getElementById('duration').textContent = sound.duration.toFixed(2) + ' s';
        });

        sound.addEventListener('error', function () {
            say('Le fichier ne se charge pas.',
                'Le son est absent ou le serveur ne le sert pas — la lecture automatique n\'est pas en cause.',
                'blocked');
            write('error: ' + (sound.error ? 'code ' + sound.error.code : 'inconnue'));
        });

        // Exactly what the board does on a new call: play, unprompted, from a
        // timer rather than a click.
        function probe() {
            let played;
            try {
                sound.currentTime = 0;
                played = sound.play();
            } catch (e) {
                say('Lecture automatique bloquée.', String(e), 'blocked');
                return;
            }

            if (!played) {
                // Old browsers return nothing; fall back to watching the clock.
                setTimeout(function () {
                    if (sound.currentTime > 0 && !sound.paused) {
                        say('Le son passe. La borne sonnera toute seule.', null, 'ok');
                    } else {
                        say('Lecture automatique bloquée.',
                            'Démarrez Chrome avec --autoplay-policy=no-user-gesture-required.',
                            'blocked');
                    }
                }, 500);

                return;
            }

            played.then(function () {
                say('Le son passe. La borne sonnera toute seule.', null, 'ok');
                write('play() résolu');
            }).catch(function (e) {
                say('Lecture automatique bloquée.',
                    'Démarrez Chrome avec --autoplay-policy=no-user-gesture-required. (' + e.name + ')',
                    'blocked');
                write('play() rejeté: ' + e.name + ' — ' + e.message);
            });
        }

        document.getElementById('manual').addEventListener('click', function () {
            sound.currentTime = 0;
            const played = sound.play();
            write('lecture manuelle demandée');
            if (played) {
                played.catch(function (e) {
                    write('lecture manuelle rejetée: ' + e.name);
                });
            }
        });

        probe();
    })();
</script>

</body>
</html>
