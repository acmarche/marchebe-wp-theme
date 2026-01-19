<?php

namespace AcMarche\Theme\Lib\Sort;

add_action('category_edit_form_fields', [$this, 'category_metabox']);
add_action('edited_category', [$this, 'save_category_metadata']);

class CategoryMetaBox
{
    public function category_metabox($term)
    {

        $single = true;
        $term_id = $term->term_id;
        $value = get_term_meta($term_id, 'acmarche_category_sort', $single);
        ?>
        <table class="form-table">
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="AcSort">Trier les articles par</label>
                </th>
                <td>
                    <select name="acmarche_category_sort" id="acmarche_category_sort">
                        <option value="0"></option>
                        <option value="post_title_asc"<?php echo $value == 'post_title_asc' ? ' selected="selected"' : '' ?>>
                            Titre (A=>Z)
                        </option>
                        <option value="post_title_desc"<?php echo $value == 'post_title_desc' ? ' selected="selected"' : '' ?>>
                            Titre (Z=>A)
                        </option>
                        <option value="post_date_asc"<?php echo $value == 'post_date_asc' ? ' selected="selected"' : '' ?>>
                            Date de création (Asc)
                        </option>
                        <option value="post_date_desc"<?php echo $value == 'post_date_desc' ? ' selected="selected"' : '' ?>>
                            Date de création (Desc)
                        </option>
                        <option value="post_modified"<?php echo $value == 'post_modified' ? ' selected="selected"' : '' ?>>
                            Date de modification
                        </option>
                        <option value="manual"<?php echo $value == 'manual' ? ' selected="selected"' : '' ?>>
                            Manuellement
                        </option>
                    </select>
                    <p class="description">Par défaut les articles sont triés par titre croissant.</p>
                </td>
            </tr>
        </table>
        <?php

    }

    public function save_category_metadata($term_id)
    {

        if (current_user_can('manage_categories')) {

            $meta_key = 'acmarche_category_sort';
            $value = isset($_POST[$meta_key]) ? $_POST[$meta_key] : false;

            if ($value && $value != '') {
                $t = update_term_meta($term_id, $meta_key, $value);
            } else {
                delete_term_meta($term_id, $meta_key);
            }
        }
    }

}
