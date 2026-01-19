<?php

add_action('pre_get_posts', 'modify_where_category');

function modify_where_category(WP_Query $query) {
    //if (!is_admin() && $query->is_main_query() && !$query->get('cat'))
    if (!is_admin() && is_category()) :

        $object = get_queried_object();

        if ($object != null) {

            if ($object->cat_ID) {

                if ($query->is_main_query()) {

                    $ID_cat = $object->cat_ID;

                    $category_order = get_term_meta($ID_cat, 'acmarche_category_sort', true);

                    switch ($category_order) {
                        case 'post_title_desc' :
                            $query->set('order', 'DESC');
                            $query->set('orderby', 'post_title');
                            break;
                        case 'post_date_asc' :
                            $query->set('order', 'ASC');
                            $query->set('orderby', 'date');
                            break;
                        case 'post_date_desc' :
                            $query->set('order', 'DESC');
                            $query->set('orderby', 'date');
                            break;
                        case 'post_modified_asc' :
                            $query->set('order', 'ASC');
                            $query->set('orderby', 'modified');
                            break;
                        case 'post_modified_desc' :
                            $query->set('order', 'DESC');
                            $query->set('orderby', 'modified');
                            break;
                        default :
                            $query->set('order', 'ASC');
                            $query->set('orderby', 'post_title');
                            break;
                    }
                    //sinon prend enfant
                    $query->set('category__in', $ID_cat);

                    $types = array('post', 'bottin_fiche', 'acpatrimoine', 'hades_event', 'hades_logement');
                    $query->set('post_type', $types);
                }
            }
        }
    endif;
}
