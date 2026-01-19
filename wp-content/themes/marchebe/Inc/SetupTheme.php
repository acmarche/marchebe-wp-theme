<?php

namespace AcMarche\Theme\Inc;

class SetupTheme
{
    public function __construct()
    {
        $this->setup();
    }

    /**
     *
     */
    function setup(): void
    {
        add_theme_support('post-thumbnails');
        add_image_size('news-thumbnail', 800, 450, true);
        // Hero image for article headers (full-width, responsive heights)
        add_image_size('hero-header', 1920, 1080, true);

        add_theme_support(
            'post-formats',
            array('aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video')
        );
        /*
         * Switch default core markup for search form, comment form, and comments
         * to output valid HTML5.
         */
        add_theme_support(
            'html5',
            array(
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
                'navigation-widgets',
            )
        );

        // Add support for Block Styles.
        add_theme_support('wp-block-styles');

        // Add support for full and wide align images.
        add_theme_support('align-wide');

        // Add support for editor styles.
        //add_theme_support( 'editor-styles' );

        // Add support for responsive embedded content.
        add_theme_support('responsive-embeds');
    }
}
