<?php

namespace AcMarche\Theme\Lib;

use AcMarche\Theme\Lib\Helper\CookieHelper;
use Twig\TwigFunction;

class TwigFunctions
{
    public static function fichePhones(): TwigFunction
    {
        return new TwigFunction(
            'fichePhones',
            function (\stdClass $fiche): array {
                $phoneFields = [
                    $fiche->telephone ?? null,
                    $fiche->telephone_autre ?? null,
                    $fiche->gsm ?? null,
                    $fiche->contact_gsm ?? null,
                    $fiche->contact_telephone ?? null,
                    $fiche->contact_telephone_autre ?? null,
                ];

                return array_values(array_filter($phoneFields, fn($phone) => !empty($phone)));
            }
        );
    }

    public static function ficheEmails(): TwigFunction
    {
        return new TwigFunction(
            'ficheEmails',
            function (\stdClass $fiche): array {
                $emailFields = [
                    $fiche->email ?? null,
                    $fiche->contact_email ?? null,
                ];

                return array_values(array_filter($emailFields, fn($email) => !empty($email)));
            }
        );
    }

    public static function ficheUrlMap(): TwigFunction
    {
        return new TwigFunction(
            'ficheUrlMap',
            function (\stdClass $fiche): string {
                if ($fiche->latitude && $fiche->longitude) {
                    return "https://www.google.com/maps/search/?api=1&query=".$fiche->latitude.",".$fiche->longitude;
                }

                $address = "$fiche->rue $fiche->numero , $fiche->cp $fiche->localite";

                return "https://www.google.com/maps/search/?api=1&query=".urlencode($address);

            }
        );
    }

    public static function cookieIsAuthorizedByName(): TwigFunction
    {
        return new TwigFunction(
            'cookieIsAuthorizedByName',
            function (string $name): bool {
                return CookieHelper::isAuthorizedByName($name);
            }
        );
    }

    public static function cookieHasSetPreferences(): TwigFunction
    {
        return new TwigFunction(
            'cookieHasSetPreferences',
            function (): bool {
                return CookieHelper::hasSetPreferences();
            }
        );
    }
}