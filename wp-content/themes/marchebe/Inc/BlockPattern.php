<?php

namespace AcMarche\Theme\Inc;

/**
 * Registers the editor block patterns for the marchebe theme.
 *
 * "Travaux en cours" is a structured article type used to communicate about a
 * roadworks / construction site (see data/Article-type.pdf). Editors create a
 * new post, insert the pattern and fill in the placeholders between brackets.
 *
 * The front-end styling of the rendered body lives in assets/css/tailwind.css
 * under the ".travaux-fiche" section (the Twig display counterpart is
 * templates/article/_travaux.html.twig).
 */
class BlockPattern
{
    private const CATEGORY = 'marchebe';

    public function __construct()
    {
        add_action('init', [$this, 'register']);
    }

    public function register(): void
    {
        if (!function_exists('register_block_pattern')) {
            return;
        }

        register_block_pattern_category(
            self::CATEGORY,
            ['label' => __('Marche-en-Famenne', 'marchebe')]
        );

        register_block_pattern(
            'marchebe/travaux-en-cours',
            [
                'title' => __('Travaux en cours', 'marchebe'),
                'description' => __(
                    'Fiche structurée pour annoncer un chantier : période, localisation, mesures de circulation, plans et déviations.',
                    'marchebe'
                ),
                'categories' => [self::CATEGORY],
                'keywords' => ['travaux', 'chantier', 'voirie', 'déviation'],
                'content' => $this->travauxContent(),
            ]
        );
    }

    /**
     * Gutenberg block markup for the "Travaux en cours" pattern.
     * Placeholders between [brackets] are meant to be replaced by the editor.
     */
    private function travauxContent(): string
    {
        return <<<'HTML'
<!-- wp:group {"className":"travaux-fiche"} -->
<div class="wp-block-group travaux-fiche not-prose">

<!-- wp:paragraph {"className":"travaux-row"} -->
<p class="travaux-row"><strong>Période des travaux</strong> Du [date] au [date]</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"travaux-note"} -->
<div class="wp-block-group travaux-note not-prose">
<!-- wp:paragraph -->
<p>Les dates mentionnées sont données à titre indicatif et peuvent être adaptées en fonction des conditions météorologiques, des contraintes techniques ou de l'avancement du chantier.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"travaux-row"} -->
<p class="travaux-row"><strong>Localisation</strong> [Rue(s), quartier ou village concerné(s)]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"travaux-row"} -->
<p class="travaux-row"><strong>Type de chantier</strong> [Voirie / Trottoirs / Égouttage / Impétrants / Éclairage public / Construction / Aménagement de sécurité / Autre]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"travaux-row"} -->
<p class="travaux-row"><strong>Objet des travaux</strong> [Brève description en une ou deux phrases]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"travaux-row"} -->
<p class="travaux-row"><strong>Maître d'ouvrage</strong> [Ville de Marche-en-Famenne / SPW Mobilité et Infrastructures / SWDE / ORES / Proximus / Infrabel / Autre]</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Informations complémentaires</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"travaux-row"} -->
<p class="travaux-row"><strong>Ordonnance / arrêté de police</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Les mesures de circulation applicables durant les travaux sont définies par une ordonnance ou un arrêté de police.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Télécharger le document</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"className":"travaux-row"} -->
<p class="travaux-row"><strong>Plan du chantier</strong></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Télécharger le plan</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"className":"travaux-row"} -->
<p class="travaux-row"><strong>Plan de déviation</strong></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Télécharger le plan</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Autres documents</h3>
<!-- /wp:heading -->

<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Courrier riverains</a></div>
<!-- /wp:button -->

<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Autre</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

</div>
<!-- /wp:group -->
HTML;
    }
}
