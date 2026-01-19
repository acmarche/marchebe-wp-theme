<?php

namespace AcMarche\Theme\Lib\Bottin;

use AcMarche\Theme\Lib\Twig;

class Bottin
{
    public const COMMERCES = 610;
    public const LIBERALES = 591;
    public const PHARMACIES = 390;
    public const ECO = 511;
    public const SANTECO = 636;

    public const ALL = [self::COMMERCES, self::LIBERALES, self::PHARMACIES, self::ECO, self::SANTECO];

    public static function getUrlBottin(): string
    {
        return $_ENV['DB_BOTTIN_URL'].'/bottin/fiches/';
    }

    public static function getUrlDocument(): string
    {
        return $_ENV['DB_BOTTIN_URL'].'/bottin/documents/';
    }

    public function getImageUrl(): void
    {
        //  /public/bottin/fiches/
    }

    public static function getExcerpt(\stdClass $fiche): string
    {
        $twig = Twig::LoadTwig();

        return $twig->render(
            '@AcMarche/bottin/_fiche_excerpt.html.twig',
            [
                'fiche' => $fiche,
            ]
        );
    }

    public static function isEconomic()
    {

    }
}