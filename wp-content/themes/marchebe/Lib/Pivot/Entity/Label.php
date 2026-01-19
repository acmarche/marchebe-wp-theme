<?php

namespace AcMarche\Theme\Lib\Pivot\Entity;

readonly class Label
{
    public function __construct(
        public string $lang,
        public string $value
    ) {}
}