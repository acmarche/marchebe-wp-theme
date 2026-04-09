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

    public const array ALL = [self::COMMERCES, self::LIBERALES, self::PHARMACIES, self::ECO, self::SANTECO];

    public static function urlImage(int $ficheId,array $image): string
    {
        return $_ENV['DB_BOTTIN_URL'].'/storage/bottin/fiches/'.$ficheId.'/'.$image['file_name'];
    }

    public static function getUrlDocument(): string
    {
        return $_ENV['DB_BOTTIN_URL'].'/storage/bottin/documents/';
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