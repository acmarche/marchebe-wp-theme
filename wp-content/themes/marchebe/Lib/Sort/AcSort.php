<?php

/*
  Plugin Name: AcMarche Sort
  Plugin URI: https://wordpress.org/plugins/black-studio-tinymce-widget/
  Description: Trier les articles par catégories
  Version: 1.0
  Author: Cst
  Author URI: http://cst.marche.be
  Requires at least: 3.1
  Tested up to: 4.0
  License: GPLv3
  Text Domain: acmarche-sort
  Domain Path: /languages
 */

namespace AcMarche\Theme\Lib\Sort;

use WP_Error;
use WP_Post;
use wpdb;

require_once 'PageSorting.php';
require_once 'CategoryMetaBox.php';
require_once 'SortLink.php';

add_action(
    'admin_menu',
    function () {
        PageSorting::loadPages();
    }
);

add_action(
    'admin_enqueue_scripts',
    function () {
        AcSort::load_files();
    }
);

add_action(
    'wp_ajax_update-custom-type-order',
    function () {
        AcSort::saveNewsOrder();
    }
);

add_action(
    'wp_ajax_acupdate-sort',
    function () {
        AcSort::saveArticlesOrder();
    }
);

class AcSort
{
    public static function load_files()
    {
        wp_enqueue_script(
            'jquery-ui-sortable',
            null,
            array("jquery", "jquery-ui-core", "interface", "jquery-ui-sortable", "wp-lists", "jquery-ui-sortable")
        );
        wp_enqueue_style(
            'marchebe-sort-style',
            plugin_dir_url(__FILE__).'/css/cpt.css',
            array(),
            wp_get_theme()->get('Version')
        );
    }

    /**
     * @param WP_Post[] $news
     *
     * @return  WP_Post[]
     */
    public static function trieNews(array $news): array
    {
        // dump($news);
        //obtient table avec id news id blog order
        $ordre = self::getOrdreNews();
        /*
         * je check si chaque news a un classement
         * si oui set position
         */
        array_map(
            function ($post) use ($ordre) {
                $post->order = 0;
                $needle = array_filter(
                    $ordre,
                    function ($e) use ($post) {
                        if ($post->ID == $e->id_news || $post->blod_id == $e->id_blog) {
                            return $e;
                        }

                        return null;
                    }
                );
                if (count($needle) > 0) {
                    $post->order = (int)end($needle)->order;
                }
            },
            $news
        );
        //  dump($news);
        $news = SortUtil::sortByPosition($news);

        //   dump($news);

        return $news;
    }

    public static function getOrdreNews(): array
    {
        global $wpdb;
        $query = "SELECT * FROM `news_order` ORDER BY `order` ";

        $num_rows = $wpdb->query($query);

        if ($wpdb->last_error) {

            return [];
        }

        if ($num_rows == 0) {
            return array();
        }

        return $wpdb->get_results($query);
    }

    static function saveNewsOrder(): void
    {
        global $wpdb;
        parse_str($_POST['order'], $data);
        $i = 0;
        $type = null;
        foreach ($data as $clef => $tab) :
            $vars = array();
            $varsUp = array();
            list($nul, $post_id) = explode("_", $clef);
            $blog_id = $tab[0];
            $vars["id_blog"] = $blog_id;
            $vars["id_news"] = $post_id;
            $vars["order"] = $i;
            $varsUp["order"] = $i;
            $i++;

            $where = array('id_blog' => $blog_id, 'id_news' => $post_id);

            //try update if existe
            $update = $wpdb->update('news_order', $varsUp, $where);

            if ($wpdb->last_error) {
                $error = "Impossible update. Erreur : <br />";
                echo $wpdb->last_error;
                echo $wpdb->last_query;
                $wp_error = new WP_Error(200, $error);
                echo $wp_error->get_error_message();
            } elseif ($update == 0) {
                /*
                 * check if already in table
                 */
                $query = "SELECT * FROM `news_order` WHERE `id_news` = %s AND `id_blog` = %s ";

                $num_rows = $wpdb->query($wpdb->prepare($query, $post_id, $blog_id));

                if ($wpdb->last_error) {
                    echo '<p style="color: red;">Impossible d\'obtenir les infos de la news. Erreur : <br />'.
                        $wpdb->last_error.'</p>';

                    return;
                }

                if ($num_rows == 0) {

                    $wpdb->insert('news_order', $vars, $type);

                    if ($wpdb->last_error) {

                        $error = "Impossible d'enregistrer la commande. Erreur : <br />";
                        echo $wpdb->last_error;
                        echo $wpdb->last_query;
                        $wp_error = new WP_Error(200, $error);
                        echo $wp_error->get_error_message();
                    } else {
                       // $wpdb->insert_id;
                    }
                }
            }
        endforeach;

        if (is_array($data)) {
            foreach ($data as $key => $values) {
                if ($key == 'item') {
                    foreach ($values as $position => $id) {
                        $wpdb->update(
                            $wpdb->posts,
                            array('menu_order' => $position, 'post_parent' => 0),
                            array('ID' => $id)
                        );
                    }
                } else {
                    foreach ($values as $position => $id) {
                        $wpdb->update(
                            $wpdb->posts,
                            array('menu_order' => $position, 'post_parent' => str_replace('item_', '', $key)),
                            array('ID' => $id)
                        );
                    }
                }
            }
        }
    }

    /**
     * Retourne les id des items dans l'ordre
     *
     * @param int $cat_id
     *
     * @return array
     * @global wpdb $wpdb
     */
    static function getOrderOfItems(int $cat_id): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'acposts_order';

        $query = "SELECT * FROM $table_name WHERE `cat_id` = '$cat_id' ORDER BY `sort` ";

        $num_rows = $wpdb->query($query);

        if ($wpdb->last_error) {
            $error = $wpdb->last_error;

            return array();
        }

        if ($num_rows == 0) {
            return array();
        }

        $rows = $wpdb->get_results($query, ARRAY_A);

        return $rows;
    }

    /**
     * Retourne les articles tries
     *
     * @param int $cat_id
     * @param array object post $items
     *
     * @return array
     */
    static function getSortedItems(int $cat_id, array $posts): array
    {
        $items_trie = array();
        $items_not_sorted = array();
        //obtient table avec id news id blog order
        $ordre = self::getOrderOfItems($cat_id);

        self::deleteOldReference($ordre);

        if (count($ordre) > 0) {

            /**
             * je check si chaque post a un classement
             * si oui va dans un tableau temporaire qui sera en premier
             */
            foreach ($ordre as $item) {
                $cat_id = $item["cat_id"];
                $post_id = $item["post_id"];

                foreach ($posts as $key => $post) {
                    if (!isset($post->ID)) {
                        continue;
                    }
                    if ($post_id == $post->ID) {
                        $items_trie[] = $post;
                        unset($posts[$key]);
                    }
                }
            }

            /**
             * ici je prends les news non classes
             */
            foreach ($posts as $cat_id => $post) {
                //$post->blog_id = $blog_id;
                $items_not_sorted[] = $post;
            }

            $t = array_merge($items_trie, $items_not_sorted);

            return $t;
        }

        return $posts;
    }

    /**
     * TO DO
     *
     * @param mixed $ordre
     */
    protected static function deleteOldReference($ordre)
    {
        foreach ($ordre as $item) {
            $post_id = $item["post_id"];
            $post = get_post($post_id);
            if (!$post) {
                //   var_dump($post_id);
            }
        }
    }

    public static function saveArticlesOrder()
    {
        global $wpdb;

        $table_name = $wpdb->prefix.'acposts_order';

        parse_str($_POST['order'], $data);
        $cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;

        if ($cat_id) {

            if (is_array($data)) :
                foreach ($data as $key => $values) {

                    foreach ($values as $ordre => $post_id) {
                        $where = array('cat_id' => $cat_id, 'post_id' => $post_id);
                        $up = array('sort' => $ordre);

                        //try update if existe
                        $update = $wpdb->update($table_name, $up, $where);
                        echo "update $update <br />";

                        if ($wpdb->last_error) {
                            $error = "Impossible update. Erreur : <br />";
                            $error .= $wpdb->last_error;
                            $error .= $wpdb->last_query;
                            $wp_error = new WP_Error(200, $error);
                            $error .= $wp_error->get_error_message();
                            mail('jf@marche.be', "error", $error);
                        }
                        if (!$update) {

                            /**
                             * check if already in table
                             */
                            $query = "SELECT * FROM $table_name WHERE `post_id` = %s AND `cat_id` = %s ";
                            $num_rows = $wpdb->query($wpdb->prepare($query, $post_id, $cat_id));

                            if ($num_rows == 0) {
                                $vars = array('cat_id' => $cat_id, 'post_id' => $post_id, 'sort' => $ordre);
                                $wpdb->insert($table_name, $vars);
                                if ($wpdb->last_error) {
                                    $error = "Impossible update. Erreur : <br />";
                                    $error .= $wpdb->last_error;
                                    $error .= $wpdb->last_query;
                                    $wp_error = new WP_Error(200, $error);
                                    $error .= $wp_error->get_error_message();
                                    mail('jf@marche.be', "error", $error);
                                }
                            }
                        }
                    }
                }
            endif;
        }
    }
}
