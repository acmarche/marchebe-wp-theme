<?php

namespace AcMarche\Theme\Lib\Pivot\Entity;

class Document
{
    public ?string $name = null;
    public ?string $desc = null;
    public ?string $url = null;
    public ?string $extension = null;
    public array|null $link = [];
    public ?string $codeCgt = null;
    public ?string $urn = null;
}
