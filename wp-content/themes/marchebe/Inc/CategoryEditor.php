<?php

namespace AcMarche\Theme\Inc;

class CategoryEditor
{
    public function __construct()
    {
        // Remove filters that strip HTML from category descriptions
        remove_filter('pre_term_description', 'wp_filter_kses');
        remove_filter('term_description', 'wp_kses_data');
        // Add visual editor to category description
        add_action('category_edit_form_fields', [$this, 'setEditor'], 1);
        // Hide the default description field
        add_action('admin_head', function () {
            echo '<style>.term-description-wrap { display: none; }</style>';
        });
    }

    public static function setEditor($term): void
    {
        ?>
        <tr class="form-field">
            <th scope="row"><label for="description"><?php _e('Description'); ?></label></th>
            <td>
                <?php
                wp_editor(
                    html_entity_decode($term->description),
                    'description',
                    array('media_buttons' => false, 'textarea_rows' => 10)
                );
                ?>
            </td>
        </tr>
        <?php
    }


}