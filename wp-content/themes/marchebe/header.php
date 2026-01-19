<?php

namespace AcMarche\Theme;

use AcMarche\Theme\Data\Data;
use AcMarche\Theme\Lib\Helper\CookieHelper;
use AcMarche\Theme\Lib\Twig;
use AcMarche\Theme\Repository\MenuRepository;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

CookieHelper::createCookie([]);
?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <?php
        //Twig::rendPage('@AcMarche/header/header/_analytics.html.twig');
        ?>
        <meta charset="<?php bloginfo('charset'); ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="author" content="Epn">
        <meta name="author" content="Esquare">
        <?php
        Twig::rendPage('@AcMarche/header/_favicons.html.twig');
        wp_head();
        ?>
    </head>
<body id="app" <?php body_class(); ?> >
    <?php
wp_body_open();

$wpRepository = new MenuRepository();
$menu = $wpRepository->getMenu();

$twig = Twig::loadTwig();
try {
    echo $twig->render('@AcMarche/_header.html.twig', [
            'menu_data' => json_encode($menu),
            'menuItems' => Data::menuItems,
            'hasNotAcceptCookie' => !CookieHelper::isAuthorizedByName(CookieHelper::$essential),
    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}