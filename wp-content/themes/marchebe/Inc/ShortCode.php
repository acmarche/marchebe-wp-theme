<?php

namespace AcMarche\Theme\Inc;

use AcMarche\Issep\Indice\IndiceEnum;
use AcMarche\Theme\Lib\Cache;
use AcMarche\Theme\Lib\Capteur;
use AcMarche\Theme\Lib\Helper\CookieHelper;
use AcMarche\Theme\Lib\Twig;
use AcMarche\Theme\Repository\ConseilRepository;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function json_decode;

class ShortCode
{
    private HttpClientInterface $httpClient;

    public function __construct()
    {
        add_action('init', [$this, 'registerShortcodes']);
    }

    function registerShortcodes()
    {
        add_shortcode('taxe', [$this, 'taxe']);
        add_shortcode('enaos', [$this, 'enaos']);
        add_shortcode('conseil_archive', [$this, 'conseilPv']);
        add_shortcode('conseil_ordre', [$this, 'conseilOrdre']);
        add_shortcode('google_map', [$this, 'googleMap']);
        add_shortcode('capteur_list', [$this, 'capteurList']);
        add_shortcode('capteur_color', [$this, 'capteurColor']);
    }

    public function conseilOrdre(): string
    {
        $conseilDb = new ConseilRepository();
        $ordres = $conseilDb->getAllOrdre();
        $twig = Twig::LoadTwig();

        return $twig->render(
            '@AcMarche/conseil/_ordre.html.twig',
            [
                'ordres' => $ordres,
            ]
        );
    }

    public function conseilPv(): string
    {
        $conseilRepository = new ConseilRepository();
        $pvs = $conseilRepository->findFromDb();
        $files = $conseilRepository->findFromDirectory();
        $all = [...$pvs, ...$files];
        $allGrouped = [];
        foreach ($all as $item) {
            $allGrouped[$item['year']][] = $item;
        }

        $twig = Twig::LoadTwig();

        return $twig->render(
            '@AcMarche/conseil/_pv.html.twig',
            [
                'pvs' => $allGrouped,
            ]
        );
    }

    public function enaos(): ?string
    {
        if (!CookieHelper::isAuthorizedByName(CookieHelper::$encapsulated)) {
            return '';
        }
        $cacheKey = Cache::generateKey('enaos');

        return Cache::get($cacheKey, function () {
            return file_get_contents('https://api.marche.be/marchebe/necrologie/');
            $content = wp_remote_get('https://api.marche.be/marchebe/necrologie/');//timeout
            if ($content instanceof \WP_Error) {
                return $content->get_error_message();
            } else {
                return $content['body'];
            }
        }
        );
    }

    public function taxe(): string
    {
        $cacheKey = Cache::generateKey('liste_taxes');

        return Cache::get($cacheKey, function () {
            $this->httpClient = HttpClient::create();
            $nomenclatures = $this->getContentTaxe('/taxes/api2');
            if ($nomenclatures) {
                $twig = Twig::LoadTwig();
                $template = '@AcMarche/article/_taxe.html.twig';

                return $twig->render($template, ['nomenclatures' => $nomenclatures]);
            }

            return 'Erreur de chargement de la page';
        });
    }

    private function getContentTaxe(string $url)
    {
        $base = 'https://extranet.marche.be';
        try {
            $response = $this->httpClient->request('GET', $base.$url);

            return json_decode($response->getContent());
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {

            return null;
        }
    }

    public function googleMap(array $args): string
    {
        $latitude = $args['lat'] ?? null;
        $longitude = $args['long'] ?? null;

        if (!$latitude || !$longitude) {
            return '';
        }

        // Check if encapsulated cookies are authorized
        $encapsulatedAllowed = CookieHelper::isAuthorizedByName(CookieHelper::$encapsulated);

        // Enqueue Leaflet CSS and JS only when shortcode is used and encapsulated cookies are allowed
        if ($encapsulatedAllowed) {
            wp_enqueue_style(
                'leaflet-css',
                Assets::leaflet_css,
                [],
                '1.9.4'
            );

            wp_enqueue_script(
                'leaflet-js',
                Assets::leaflet_js,
                [],
                '1.9.4',
                true // Load in footer
            );
        }

        $twig = Twig::LoadTwig();
        $post = get_post();
        $title = $post ? $post->post_title : '';

        try {
            $content = $twig->render(
                '@AcMarche/widgets/_map.html.twig',
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'title' => $title,
                    'encapsulatedAllowed' => $encapsulatedAllowed,
                ]
            );
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            return "Erreur de chargement de la carte. ".$e->getMessage();
        }

        //bug avec raw de twig

        return preg_replace("#\n#", "", $content);
    }

    public function capteurList(): string
    {
        $twig = Twig::LoadTwig();
        $capteur = new Capteur();

        return $twig->render(
            '@AcMarche/capteur/_list.html.twig',
            [
                'stations' => $capteur->getStations(),
            ]
        );
    }

    public function capteurColor(): string
    {
        $twig = Twig::LoadTwig();

        return $twig->render(
            '@AcMarche/capteur/_colors.html.twig',
            [
                'indices' => IndiceEnum::cases(),
            ]
        );
    }

}
