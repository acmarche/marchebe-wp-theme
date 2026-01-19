<?php

namespace AcMarche\Theme\Lib\Pivot\Helper;

use Carbon\Carbon;

class DateHelper
{
    public static function convertStringToDateTime(string $dateString, string $fromFormat = "d/m/Y"): \DateTimeInterface
    {
        return Carbon::createFromFormat($fromFormat, $dateString)->toDateTime();
    }
}