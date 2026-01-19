<?php

namespace AcMarche\Theme\Lib\Pivot\Entity;

readonly class Spec
{
    public function __construct(
        public string $urn,
        public ?string $urnCat = null,
        public ?string $urnCatLabel = null,
        public ?string $urnSubCat = null,
        public ?string $urnSubCatLabel = null,
        public ?int $order = null,
        public ?string $type = null,
        public ?string $label = null,
        public ?string $value = null,
        public ?string $valueLabel = null,
        public array $spec = []
    ) {}
}