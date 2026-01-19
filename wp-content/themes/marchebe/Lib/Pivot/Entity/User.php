<?php

namespace AcMarche\Theme\Lib\Pivot\Entity;

readonly class User
{
    public function __construct(
        public string $codeCgt,
        public string $login,
        public string $nom,
        public string $prenom
    ) {}
}