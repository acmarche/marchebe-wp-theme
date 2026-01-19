<?php

namespace AcMarche\Theme\Lib\Pivot\Entity;

readonly class TypeOffre
{
    public function __construct(
        public int $idTypeOffre,
        /**
         * @var array<Label>|null
         */
        public ?array $label = null
    ) {
    }

    public function isEvent(): bool
    {
        return $this->idTypeOffre === 9;
    }

    public function getLabelByLanguage(string $languageSelected): ?string
    {
        foreach ($this->label as $attributes) {
            if (isset($attributes['lang'])) {
                if ($attributes['lang'] == $languageSelected) {
                    return $attributes['value'];
                }
            }
        }

        return null;
    }
}