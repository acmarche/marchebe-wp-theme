<?php

namespace AcMarche\Theme\Lib\Pivot\Entity;

readonly class UserGlobal
{
    public function __construct(
        public int $idUserglobal,
        public string $nom
    ) {}
}
