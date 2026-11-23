<?php

namespace AcMarche\Theme\Inc;

class Assets
{
    const leaflet_js = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    const leaflet_css = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    const alpine_js = 'https://unpkg.com/alpinejs@3.17.5/dist/cdn.min.js';
    const fontawesome_css = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css';

    /**
     * Subresource Integrity for every third-party file, keyed by WordPress handle.
     * Versions are pinned in the URLs above: the browser refuses a file whose bytes
     * no longer match, instead of running whatever the CDN serves. When bumping a
     * version, update its hash too (`curl -s URL | openssl dgst -sha256 -binary | base64`).
     */
    const SRI = [
        'marchebe-alpine' => 'sha256-tANunprCL6b2rhuikuAV4fPpyF0Neec+TI65uxObUi4=',
        'marchebe-awesome' => 'sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==',
        'leaflet-js' => 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=',
        'leaflet-css' => 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=',
    ];

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this,'remove_unnecessary_core_styles'], 9999);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('enqueue_block_assets', [$this, 'enqueue_editor_styles']);
        add_action('wp_head', [$this, 'preload_fonts'], 1);
        add_filter('script_loader_tag', [$this, 'add_defer_attribute'], 10, 2);
        add_filter('script_loader_tag', [$this, 'add_integrity'], 20, 2);
        add_filter('style_loader_tag', [$this, 'add_integrity'], 20, 2);
        // Fix WordPress core asset URLs in multisite subdirectory setup
        add_filter('style_loader_src', [$this, 'fix_multisite_urls'], 10, 1);
        add_filter('script_loader_src', [$this, 'fix_multisite_urls'], 10, 1);
    }

    function enqueue_editor_styles(): void
    {
        // Only target the block editor; loading via enqueue_block_assets injects
        // the stylesheet into the editor iframe the way WordPress expects.
        if (!is_admin()) {
            return;
        }

        $themeUri = self::getThemeUri();
        wp_enqueue_style(
            'marchebe-admin',
            $themeUri.'/assets/css/admin.css',
            [],
            wp_get_theme()->get('Version')
        );
    }

    function enqueue_styles(): void
    {
        $themeUri = self::getThemeUri();
        wp_enqueue_style(
            'theme-slug-style',
            $themeUri.'/style.css'// get_stylesheet_uri()
        );

        wp_enqueue_style(
            'marchebe-primary',
            $themeUri.'/assets/css/marchebe.css',
            [],'2.12'
            //wp_get_theme()->get('Version')
        );
        wp_enqueue_style(
            'marchebe-awesome',
            self::fontawesome_css,
            [],
            null
        );
    }

    /**
     * Self-hosted Montserrat (Latin subset): start the download before the CSS
     * is parsed, so text swaps in early and the metric-matched fallback barely shows.
     */
    public function preload_fonts(): void
    {
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>'."\n",
            esc_url(self::getThemeUri().'/assets/fonts/montserrat-latin-wght-normal.woff2')
        );
    }

    function enqueue_scripts(): void
    {
        $themeUri = self::getThemeUri();

        // Header navigation component (must load before Alpine.js)
        wp_enqueue_script(
            'marchebe-header-nav',
            $themeUri.'/assets/js/header-nav.js',
            [],
            wp_get_theme()->get('Version')
        );

        // Category show component (must load before Alpine.js)
        wp_enqueue_script(
            'marchebe-category-show',
            $themeUri.'/assets/js/category-show.js',
            [],
            wp_get_theme()->get('Version')
        );

        // Image gallery component (must load before Alpine.js)
        wp_enqueue_script(
            'marchebe-image-gallery',
            $themeUri.'/assets/js/image-gallery.js',
            [],
            wp_get_theme()->get('Version')
        );

        // Alpine.js (depends on header-nav, category-show, and image-gallery)
        wp_enqueue_script(
            'marchebe-alpine',
            self::alpine_js,
            ['marchebe-header-nav', 'marchebe-category-show', 'marchebe-image-gallery'],
            null
        );
    }

    function add_defer_attribute($tag, $handle): string
    {
        // Add defer to Alpine.js and component scripts
        if (in_array($handle, ['marchebe-alpine', 'marchebe-header-nav', 'marchebe-category-show', 'marchebe-image-gallery'])) {
            return str_replace(' src', ' defer src', $tag);
        }

        return $tag;
    }

    /**
     * Adds integrity + crossorigin to the pinned third-party tags (see SRI).
     */
    public function add_integrity(string $tag, string $handle): string
    {
        if (!isset(self::SRI[$handle]) || str_contains($tag, ' integrity=')) {
            return $tag;
        }

        $attributes = sprintf(' integrity="%s" crossorigin="anonymous"', esc_attr(self::SRI[$handle]));

        return preg_replace('/<(script|link)\b/', '<$1'.$attributes, $tag, 1);
    }

    function remove_unnecessary_core_styles(): void
    {
        // Remove Classic Theme Styles (Often redundant/opinionated CSS)
        wp_dequeue_style('classic-theme-styles');

        // Remove Block Library Theme Styles (Often redundant/opinionated CSS)
        wp_dequeue_style('wp-block-library-theme');

        // Remove Duotone SVG filters (Large inline SVG definitions for image effects)
        remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');

        // DO NOT remove 'wp-block-library' (Needed for basic block structure/layouts)
        // DO NOT remove 'global-styles' (Needed for colors, typography, and layout settings)
    }

    public static function getThemeUri(): string
    {
        //replace get_template_directory_uri()
        //https://marche.local/enfance-jeunesse/wp-content/themes/marchebe
        return 'https://'.$_ENV['WP_URL_HOME'].'/wp-content/themes/marchebe';
    }

    /**
     * Fix WordPress core asset URLs in multisite subdirectory setup
     * Removes subsite path from wp-includes and wp-content URLs
     * Example: /culture/wp-includes/css/file.css -> /wp-includes/css/file.css
     */
    public function fix_multisite_urls($src): string
    {
        if (!is_multisite() || is_main_site()) {
            return $src;
        }

        // Get the current blog details to extract the path
        $current_blog = get_blog_details();
        if ($current_blog && $current_blog->path !== '/') {
            $subsite_path = trim($current_blog->path, '/');
            // Remove subsite path from wp-includes and wp-content URLs
            $src = preg_replace('#/'.$subsite_path.'/(wp-includes|wp-content)/#', '/$1/', $src);
        }

        return $src;
    }
}