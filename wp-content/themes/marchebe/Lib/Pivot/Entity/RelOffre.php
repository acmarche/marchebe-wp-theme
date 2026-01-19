<?php

namespace AcMarche\Theme\Lib\Pivot\Entity;

class RelOffre
{
    public Event $offre;

    public function __construct(
        public string $urn,
        public ?string $label,
    ) {
    }
}