<?php

namespace AcMarche\Theme\Lib\Pivot\Entity;

readonly class Adresse
{
    public function __construct(
        public ?string $rue = null,
        public ?string $numero = null,
        public ?int $idIns = null,
        public ?string $ins = null,
        public ?string $cp = null,
        public ?array $localite = null,
        public ?array $commune = null,
        public ?string $lieuDit = null,
        public ?string $province = null,
        public ?array $provinceUrn = null,
        public ?string $pays = null,
        public ?array $paysUrn = null,
        public ?float $lambertX = null,
        public ?float $lambertY = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?float $altitude = null,
        public ?bool $noaddress = null,
        public ?array $parcNaturel = null,
        public ?array $organisme = null
    ) {
    }

    public function getLocalityByLanguage(string $languageSelected): ?string
    {
        foreach ($this->localite as $attributes) {
            if (isset($attributes['lang'])) {
                if ($attributes['lang'] == $languageSelected) {
                    return $attributes['value'];
                }
            }
        }

        return null;
    }

}
