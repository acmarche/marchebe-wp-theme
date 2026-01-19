<?php

namespace AcMarche\Theme\Lib;

use AcMarche\Theme\Inc\Assets;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class Twig
{
    public static function loadTwig(?string $path = null): Environment
    {
        if (!$path) {
            $path = get_template_directory().'/templates';
        }

        $loader = new FilesystemLoader($path);

        try {
            $loader->addPath($path, 'AcMarche');
        } catch (LoaderError $e) {

        }

        $cache = $_ENV['APP_CACHE_DIR'] ?? self::getPathCache('twig');

        $twig = new Environment($loader, [
            'strict_variables' => WP_DEBUG,
            'debug' => WP_DEBUG,
            'cache' => WP_DEBUG ? false : $cache,
            'auto_reload' => true,
            'optimizations' => 0,
            'charset' => 'UTF-8',
        ]);

        if (WP_DEBUG) {
            $twig->enableDebug();
            $twig->addExtension(new DebugExtension());
        }

        locale_set_default('fr-FR');//for format date
        $twig->addGlobal('locale', 'fr');
        $twig->addGlobal('WP_DEBUG', WP_DEBUG);
        $twig->addFunction(self::currentUrl());
        $twig->addExtension(new StringExtension());
        $twig->addExtension(new IntlExtension());
        $twig->addFunction(TwigFunctions::fichePhones());
        $twig->addFunction(TwigFunctions::ficheEmails());
        $twig->addFunction(TwigFunctions::ficheUrlMap());
        $twig->addFunction(TwigFunctions::cookieIsAuthorizedByName());
        $twig->addFunction(TwigFunctions::cookieHasSetPreferences());
        $twig->addGlobal('template_directory', Assets::getThemeUri());

        return $twig;
    }

    public static function renderErrorPage(\Exception $exception): void
    {
        try {
            echo self::loadTwig()->render('@AcMarche/error/_error.html.twig', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            echo $e->getMessage();
        }
    }

    public static function renderNotFoundPage(string $message): void
    {
        try {
            echo self::loadTwig()->render('@AcMarche/error/_not_found.html.twig', [
                'message' => $message,
            ]);

            return;
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            echo $e->getMessage();
        }
    }

    /**
     * For sharing pages
     * @return TwigFunctions
     */
    private static function currentUrl(): TwigFunction
    {
        global $wp;

        $url = home_url($wp->request);

        return new TwigFunction(
            'currentUrl',
            function () use ($url): string {
                return $url;
            }
        );
    }

    private static function getPathCache(string $folder): string
    {
        return ABSPATH.'var/cache/'.$folder;
    }

    public static function rendPage(string $path, array $params = []): void
    {
        try {
            echo self::loadTwig()->render($path, $params);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            echo $e->getMessage();
        }
    }

}