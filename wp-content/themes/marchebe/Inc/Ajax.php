<?php

namespace AcMarche\Theme\Inc;

use AcMarche\Theme\Lib\Helper\CookieHelper;
use AcMarche\Theme\Repository\WpRepository;

class Ajax
{
    public function __construct()
    {
        /**
         * Update list posts when user select a category
         */
        add_action('wp_enqueue_scripts', [$this, 'setCategoryScript']);
        // Handle the AJAX request
        add_action('wp_ajax_set_category_action', [$this, 'setCategoryHandler']); // For logged-in users
        add_action('wp_ajax_nopriv_set_category_action', [$this, 'setCategoryHandler']); // For non-logged users

        /**
         * Update cookie preferences
         */
        add_action('wp_enqueue_scripts', [$this, 'setCookieScript']);
        // Handle the AJAX request
        add_action('wp_ajax_set_cookie_action', [$this, 'setCookieHandler']); // For logged-in users
        add_action('wp_ajax_nopriv_set_cookie_action', [$this, 'setCookieHandler']); // For non-logged users
    }

    // Localize script to pass Ajax URL and nonce
    public function setCategoryScript(): void
    {
        // admin_url('admin-ajax.php'), //bug route frankenphp
        $url = 'https://'.$_ENV['WP_URL_HOME'].'/wp-admin/admin-ajax.php';

        wp_localize_script('marchebe-alpine', 'wpData', array(
            'ajaxUrl' => $url,
            'categoryNonce' => wp_create_nonce('set_category_nonce'),
            'cookieNonce' => wp_create_nonce('set_cookie_nonce'),
        ));
    }

    public function setCategoryHandler(): void
    {
        check_ajax_referer('set_category_nonce', 'nonce');

        $categoryId = isset($_POST['categorySelected']) ? intval($_POST['categorySelected']) : 0;
        $currentSite = isset($_POST['currentSite']) ? intval($_POST['currentSite']) : Theme::CITOYEN;

        if (!$categoryId) {
            wp_send_json_error(['message' => 'Invalid category ID']);

            return;
        }

        $wpRepository = new WpRepository();
        switch_to_blog($currentSite);
        $posts = $wpRepository->getPostsAndFiches($categoryId);

        wp_send_json_success(['posts' => $posts]);
    }

    // Localize script to pass Ajax URL and nonce
    public function setCookieScript(): void
    {
        // This method is now redundant as both nonces are set in setCategoryScript()
        // Keeping it for backwards compatibility but it does nothing
    }

    public function setCookieHandler(): void
    {
        check_ajax_referer('set_cookie_nonce', 'nonce');

        $essential =  true;
        $statistics = isset($_POST['statistics']) ? filter_var($_POST['statistics'], FILTER_VALIDATE_BOOLEAN) : false;
        $encapsulated = isset($_POST['encapsulated']) ? filter_var(
            $_POST['encapsulated'],
            FILTER_VALIDATE_BOOLEAN
        ) : false;

        $preferences = [
            'essential' => $essential,
            'statistics' => $statistics,
            'encapsulated' => $encapsulated,
        ];

        // Save all preferences at once
        CookieHelper::saveAll($preferences);

        wp_send_json_success([
            'message' => 'Cookie preferences saved',
            'preferences' => $preferences,
        ]);
    }
}