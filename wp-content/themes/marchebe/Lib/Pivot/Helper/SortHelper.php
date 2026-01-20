<?php

namespace AcMarche\Theme\Lib\Pivot\Helper;

use AcMarche\Theme\Lib\Pivot\Entity\Event;
use AcMarche\Theme\Lib\Pivot\Entity\EventDate;

class SortHelper
{
    /**
     * @param array<int,Event> $events
     * @return array<int,Event>
     */
    public static function sortEvents(array &$events, string $order = 'ASC'): void
    {
        usort($events, function ($a, $b) {
            $dateA = !empty($a->dates) ? $a->dates[0] : null;
            $dateB = !empty($b->dates) ? $b->dates[0] : null;

            // Handle null cases (empty dates arrays)
            if ($dateA === null && $dateB === null) {
                return 0;
            }
            if ($dateA === null) {
                return 1; // Push events without dates to the end
            }
            if ($dateB === null) {
                return -1;
            }

            return $dateA <=> $dateB;
        });
    }

    /**
     * @param EventDate[]|array $dates
     *
     * @return EventDate[]
     */
    public static function sortDatesEvent(array $dates, string $order = 'ASC'): array
    {
        usort(
            $dates,
            fn(EventDate $a, EventDate $b) => ($order === 'ASC' ? 1 : -1) *
                ($a->dateRealBegin->getTimestamp() <=> $b->dateRealBegin->getTimestamp()),
        );

        return $dates;
    }
}