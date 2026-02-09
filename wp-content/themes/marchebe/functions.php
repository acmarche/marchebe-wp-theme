<?php

namespace AcMarche\Theme;

use AcMarche\Theme\Inc\Ajax;
use AcMarche\Theme\Inc\Assets;
use AcMarche\Theme\Inc\BottinCategoryMetaBox;
use AcMarche\Theme\Inc\CategoryEditor;
use AcMarche\Theme\Inc\OpenGraph;
use AcMarche\Theme\Inc\RestApi;
use AcMarche\Theme\Inc\RouterBottin;
use AcMarche\Theme\Inc\RouterEnquete;
use AcMarche\Theme\Inc\RouterEvent;
use AcMarche\Theme\Inc\SecurityConfig;
use AcMarche\Theme\Inc\SetupTheme;
use AcMarche\Theme\Inc\ShortCode;
use AcMarche\Theme\Inc\WpEventsSubscriber;
use AcMarche\Theme\Lib\Seo;
use AcMarche\Theme\Lib\Sort\AcSort;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;

/**
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 */

if (WP_DEBUG === false) {
    HtmlErrorRenderer::setTemplate(get_template_directory().'/error500.php');
} else {
    Debug::enable();
}
new SetupTheme();
new Assets();
new Ajax();
new RouterEvent();
new RouterBottin();
new RouterEnquete();
new BottinCategoryMetaBox();
new SecurityConfig();
new Seo();
new OpenGraph();
new ShortCode();
new WpEventsSubscriber();
new RestApi();
new AcSort();
//new CategoryEditor();