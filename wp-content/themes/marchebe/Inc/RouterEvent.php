<?php

namespace AcMarche\Theme\Inc;

class RouterEvent
{
    const PARAM_EVENT = 'codeCgt';
    const ROUTE = 'agenda-des-manifestations/manifestation';
    const SINGLE_EVENT = 'single_event';

    public function __construct()
    {
        if (get_current_blog_id() === Theme::TOURISME) {
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
        add_rewrite_rule(
            self::ROUTE.'/([a-zA-Z0-9-]+)[/]?$',
            'index.php?single_event=1&'.self::PARAM_EVENT.'=$matches[1]',  // Query vars
            'top'  // Priority
        );
    }

    function add_query_vars($vars)
    {
        $vars[] = self::SINGLE_EVENT;
        $vars[] = self::PARAM_EVENT;

        return $vars;
    }

    function add_template($template)
    {
        // Check if this is our custom route
        if (get_query_var(self::SINGLE_EVENT)) {
            $codeCgt = get_query_var(self::PARAM_EVENT);

            // Check if codeCgt exists
            if ($codeCgt) {
                // Look for template in theme directory
                $custom_template = locate_template('single_event.php');

                if ($custom_template) {
                    return $custom_template;
                }
            }
        }

        return $template;
    }

}