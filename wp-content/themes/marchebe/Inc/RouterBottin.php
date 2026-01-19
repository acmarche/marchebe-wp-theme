<?php

namespace AcMarche\Theme\Inc;

use AcMarche\Theme\Repository\BottinRepository;

class RouterBottin
{
    public const PARAM_FICHE = 'slugfiche';
    public const FICHE_ROUTE = 'bottin/fiche/';
    public const SINGLE_FICHE = 'single_fiche';
    public const PARAM_CATEGORY = 'slugcategory';
    public const CATEGORY_ROUTE = 'bwp/categorie';
    public const SINGLE_CATEGORY = 'single_category';
    private static ?BottinRepository $bottinRepository = null;

    public function __construct()
    {
        add_action('init', [$this, 'add_rewrite_rule']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'add_templates']);
        //Flush rewrite rules on theme activation (only once)
        register_activation_hook(__FILE__, [$this, 'flush_rules']);
        //$this->flushRoutes();
    }

    private static function getBottinRepository(): BottinRepository
    {
        if (self::$bottinRepository === null) {
            self::$bottinRepository = new BottinRepository();
        }

        return self::$bottinRepository;
    }

    public function flushRoutes(): void
    {
        if (is_multisite()) {
            $current = get_current_blog_id();
            foreach (Theme::SITES as $site) {
                switch_to_blog($site);
                flush_rewrite_rules();
            }
            switch_to_blog($current);
        } else {
            flush_rewrite_rules();
        }
    }

    function flush_rules(): void
    {
        $this->add_rewrite_rule();
        flush_rewrite_rules();
    }

    function add_rewrite_rule(): void
    {
        add_rewrite_rule(
            self::FICHE_ROUTE.'([a-zA-Z0-9-]+)[/]?$',
            'index.php?'.self::SINGLE_FICHE.'=1&'.self::PARAM_FICHE.'=$matches[1]',  // Query vars
            'top'  // Priority
        );
        add_rewrite_rule(
            self::CATEGORY_ROUTE.'/([a-zA-Z0-9-]+)[/]?$',
            'index.php?'.self::SINGLE_CATEGORY.'=1&'.self::PARAM_CATEGORY.'=$matches[1]',  // Query vars
            'top'  // Priority
        );
    }

    function add_query_vars($vars): array
    {
        $vars[] = self::SINGLE_FICHE;
        $vars[] = self::PARAM_FICHE;
        $vars[] = self::SINGLE_CATEGORY;
        $vars[] = self::PARAM_CATEGORY;

        return $vars;
    }

    function add_templates($template)
    {
        // Check if this is our custom route
        if (get_query_var(self::SINGLE_FICHE)) {
            $queryVar = get_query_var(self::PARAM_FICHE);

            // Check if exists
            if ($queryVar) {
                // Look for template in theme directory
                $custom_template = locate_template('single_fiche.php');

                if ($custom_template) {
                    return $custom_template;
                }
            }
        }
        if (get_query_var(self::SINGLE_CATEGORY)) {
            $queryVar = get_query_var(self::PARAM_CATEGORY);

            // Check if exists
            if ($queryVar) {
                // Look for template in theme directory
                $custom_template = locate_template('category_bottin.php');

                if ($custom_template) {
                    return $custom_template;
                }
            }
        }

        return $template;
    }

    public static function getUrlCategoryBottin(\stdClass $category): ?string
    {
        if (self::getBottinRepository()->isEconomy([$category]) !== null) {
            return self::generateCategoryUrlCap($category);
        }

        return self::getBaseUrlSite(Theme::ECONOMIE).self::CATEGORY_ROUTE.'/'.$category->slug;
    }

    public static function getUrlFicheBottin(int $idSite, \stdClass $fiche): string
    {
        if ($url = self::generateFicheUrlCap($fiche)) {
            return $url;
        }

        return self::getBaseUrlSite($idSite).self::FICHE_ROUTE.$fiche->slug;
    }

    /**
     * url pour recherche via le site de marche.
     */
    public static function generateFicheUrlCap(\stdClass $fiche): ?string
    {
        $categories = self::getBottinRepository()->getCategoriesOfFiche($fiche->id);
        if (self::getBottinRepository()->isEconomy($categories) === null) {
            return null;
        }

        return 'https://cap.marche.be/en_GB/commerce?id='.$fiche->id;
    }

    /**
     * url pour recherche via le site de marche.
     */
    public static function generateCategoryUrlCap(\stdClass $category): string
    {
        $parents = [574, 520, 609, 548, 582, 553, 527, 540, 534, 636, 568, 591];

        if (in_array($category->id, $parents)) {
            $categoryId = $category->id;
            $sousCategory = '';
        } else {
            $parent = self::getBottinRepository()->getCategory($category->parent_id);
            $categoryId = $parent->id;
            $sousCategory = $category->id;
        }

        return "https://cap.marche.be/en_GB/liste-commercants?search=&categorie_id=$categoryId&sous_categorie_id=$sousCategory";
    }

    /**
     * Retourne la base du blog (/economie/, /sante/, /culture/...
     */
    public static function getBaseUrlSite(?int $blodId = null): string
    {
        if (is_multisite()) {
            if (!$blodId) {
                $blodId = Theme::CITOYEN;
            }

            return get_blog_details($blodId)->path;
        }

        return '/';
    }

}