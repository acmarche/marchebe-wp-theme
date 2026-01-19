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
    public static function sortEvents(array $events, string $order = 'ASC'): array
    {
        usort($events, fn(Event $a, Event $b) => ($order === 'ASC' ? 1 : -1) *
            $a->firstRealDate()->getTimestamp() <=> $b->firstRealDate()->getTimestamp());

        return $events;
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
            fn(EventDate $a, EventDate $b) => ($order === 'ASC' ? 1 : -1) * $a->dateRealBegin->getTimestamp(
                ) <=> $b->dateRealBegin->getTimestamp(),
        );

        return $dates;
    }
}