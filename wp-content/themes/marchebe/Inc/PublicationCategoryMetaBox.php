<?php

namespace AcMarche\Theme\Inc;

use AcMarche\Theme\Repository\PublicationRepository;

/**
 * Natures de publications (deliberations.be) affichees sur la page d'une categorie.
 * Stockees en meta de terme: un tableau de slugs.
 */
class PublicationCategoryMetaBox
{
    const KEY_NAME = 'deliberations_natures';
    // present seulement dans le formulaire complet: l'edition rapide declenche aussi edited_category
    private const MARKER = 'deliberations_natures_form';

    public function __construct()
    {
        add_action('category_edit_form_fields', [$this, 'metabox_edit'], 10, 1);
        add_action('edited_category', [$this, 'save_metadata'], 10, 1);
    }

    /**
     * @return string[]
     */
    public static function getNatures(int $term_id): array
    {
        $natures = get_term_meta($term_id, self::KEY_NAME, true);

        return is_array($natures) ? $natures : [];
    }

    public static function metabox_edit($tag): void
    {
        $repository = new PublicationRepository();
        $natures = $repository->findNatures();
        $selected = self::getNatures($tag->term_id);
        $counts = array_count_values(array_filter(array_column($repository->findAll(), 'nature_slug')));

        // une nature choisie mais disparue de la liste reste cochee: l'enregistrement ne doit pas l'effacer en silence
        foreach ($selected as $slug) {
            $natures[$slug] ??= $slug;
        }
        ?>
        <tr class="form-field">
            <th scope="row"><label>Publications (deliberations.be)</label></th>
            <td>
                <input type="hidden" name="<?php echo self::MARKER; ?>" value="1">
                <?php if ($natures === []) { ?>
                    <p class="description">
                        Aucune nature disponible: lancer <code>php console deliberations:publications</code>.
                    </p>
                <?php } else { ?>
                    <fieldset style="max-height: 16rem; overflow-y: auto; padding: .5rem .75rem; border: 1px solid #c3c4c7; background: #fff;">
                        <legend class="screen-reader-text">Natures de publications</legend>
                        <?php foreach ($natures as $slug => $label) { ?>
                            <label style="display: block; margin: .25rem 0;">
                                <input type="checkbox" name="<?php echo self::KEY_NAME; ?>[]"
                                       value="<?php echo esc_attr($slug); ?>"
                                    <?php checked(in_array($slug, $selected, true)); ?>>
                                <?php echo esc_html($label); ?>
                                <span style="color: #646970;">(<?php echo (int)($counts[$slug] ?? 0); ?>)</span>
                            </label>
                        <?php } ?>
                    </fieldset>
                    <p class="description">
                        Les publications des natures cochées s'affichent sur la page de la catégorie.
                        Entre parenthèses : le nombre de publications actuellement.
                    </p>
                <?php } ?>
            </td>
        </tr>
        <?php
    }

    public static function save_metadata($term_id): void
    {
        if (!isset($_POST[self::MARKER])) {
            return;
        }

        $allowed = array_merge(array_keys((new PublicationRepository())->findNatures()), self::getNatures($term_id));
        $submitted = array_map('sanitize_key', (array)wp_unslash($_POST[self::KEY_NAME] ?? []));
        $natures = array_values(array_intersect(array_unique($submitted), $allowed));

        if ($natures !== []) {
            update_term_meta($term_id, self::KEY_NAME, $natures);
        } else {
            delete_term_meta($term_id, self::KEY_NAME);
        }
    }
}
