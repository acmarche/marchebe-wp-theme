<?php

namespace AcMarche\Theme\Lib\Pivot\Helper;

use AcMarche\Theme\Lib\Pivot\Entity\Spec;

class SpecHelper
{
    public function getValue(Spec $spec, string $urn)
    {
        $value = match ($urn) {
            'String' => $spec->value,
            'Date' => $spec->value,
            'FirstUpperStringML' => $spec->value,
            'Boolean' => $spec->value,
            'Phone' => $spec->value,
            'Choice' => $spec->value,
            'URL' => $spec->value,
            'TextML' => $spec->value,
            'Object' => $spec->value,
            'Hour' => $spec->value,
        };
    }
}