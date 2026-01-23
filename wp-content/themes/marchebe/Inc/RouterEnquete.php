<?php

namespace AcMarche\Theme\Inc;

class RouterEnquete
{
    const PARAM_ENQUETE = 'enqueteId';
    const ROUTE = 'enquetes-publiques/enquetes-publiques-urbanisme-commune-de-marche-en-famenne/enquete';
    const SINGLE_ENQUETE = 'single_enquete';

    public function __construct()
    {
        if (get_current_blog_id() === Theme::ADMINISTRATION) {
            add_action('init', [$this, 'add_rewrite_rule']);

            add_filter('query_vars', [$this, 'add_query_vars']);
            add_filter('template_include', [$this, 'add_template']);
            //Flush rewrite rules on theme activation (only once)
            register_activation_hook(__FILE__, [$this, 'flush_rules']);
        }
    }

    function flush_rules(): void
    {
        $this->add_rewrite_rule();
        flush_rewrite_rules();
    }

    function add_rewrite_rule(): void
    {
        //$apiRepository = new ApiRepository();
        //$categoryEnquete = $apiRepository->getCategoryEnquete();
        //$parent = get_category($categoryEnquete->parent);
        //$category->slug.'/([a-zA-Z0-9_-]+)/enquete/([0-9]+)/?$',
        //$parent->slug.'/'.$categoryEnquete->slug.'/'.self::PARAM_ENQUETE.'/([0-9]+)/?$',
        add_rewrite_rule(
            self::ROUTE.'/([a-zA-Z0-9-]+)[/]?$',
            'index.php?single_enquete=1&'.self::PARAM_ENQUETE.'=$matches[1]',  // Query vars
            'top'  // Priority
        );
    }

    function add_query_vars($vars)
    {
        $vars[] = self::SINGLE_ENQUETE;
        $vars[] = self::PARAM_ENQUETE;

        return $vars;
    }

    function add_template($template)
    {
        // Check if this is our custom route
        if (get_query_var(self::SINGLE_ENQUETE)) {
            $codeCgt = get_query_var(self::PARAM_ENQUETE);

            // Check if codeCgt exists
            if ($codeCgt) {
                // Look for template in theme directory
                $custom_template = locate_template('single_enquete.php');

                if ($custom_template) {
                    return $custom_template;
                }
            }
        }

        return $template;
    }

}