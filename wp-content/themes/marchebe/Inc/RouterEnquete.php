<?php

namespace AcMarche\Theme\Inc;

use AcMarche\Theme\Repository\ApiRepository;

class RouterEnquete
{
    const PARAM_ENQUETE = 'enqueteId';
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
        $apiRepository = new ApiRepository();
        $category = $apiRepository->getCategoryEnquete();
        // Match: enquetes-publiques-urbanisme-commune-de-marche-en-famenne/enquete/[id]
        //$category->slug.'/([a-zA-Z0-9_-]+)/enquete/([0-9]+)/?$',
        if ($category) {
            add_rewrite_rule(
                $category->slug.'/enquete/([0-9]+)/?$',
                'index.php?'.self::SINGLE_ENQUETE.'=1&'.self::PARAM_ENQUETE.'=$matches[2]',
                'top'
            );
        }
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
            $enqueteId = get_query_var(self::PARAM_ENQUETE);

            // Check if codeCgt exists
            if ($enqueteId) {
                // Look for template in theme directory
                $custom_template = locate_template('single_enquete.php');

                if ($custom_template) {
                    return $custom_template;
                }
            }
        }

        return $template;
    }

    function add_templateAi($template)
    {
        // Check if this is our custom route via query var (rewrite rule)
        $enqueteId = get_query_var(self::PARAM_ENQUETE);

        // If rewrite rule didn't match, check the URL directly
        // This handles URLs like: /category/post-slug/enquete/123
        if (!$enqueteId) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('#/enquete/(\d+)/?$#', $requestUri, $matches)) {
                $enqueteId = $matches[1];
                set_query_var(self::PARAM_ENQUETE, $enqueteId);
            }
        }

        if ($enqueteId) {
            $custom_template = locate_template('single_enquete.php');
            if ($custom_template) {
                return $custom_template;
            }
        }

        return $template;
    }

}