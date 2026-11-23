<?php

namespace AcMarche\Theme\Lib;

use AcMarche\Theme\Inc\Theme;

/**
 * Next session of the Conseil communal, shown on the homepage.
 *
 * The council usually meets on the first Monday of the month at 19h, with no
 * session in August. When a session moves (public holiday, extra session),
 * editors set the `next_council_date` custom field on the "Ordre du jour du
 * Conseil communal" page (administration blog) to `YYYY-MM-DD HH:MM`; that
 * date wins for as long as it is not past.
 */
class CouncilSchedule
{
    public const AGENDA_PAGE_ID = 1650;
    public const OVERRIDE_META = 'next_council_date';
    public const LIVE_URL = 'https://www.youtube.com/@VilledeMarche/streams';

    private const HOUR = 19;
    private const SKIPPED_MONTHS = [8];
    /** A session stays "next" until it has had time to end. */
    private const SESSION_LENGTH = 'PT3H';

    /**
     * @return array{iso: string, label: string, isToday: bool, agendaUrl: string, liveUrl: string}|null
     */
    public static function next(?\DateTimeImmutable $now = null): ?array
    {
        $tz = new \DateTimeZone('Europe/Brussels');
        $now = ($now ?? new \DateTimeImmutable('now'))->setTimezone($tz);

        $date = self::override($now, $tz) ?? self::computed($now, $tz);
        if (!$date) {
            return null;
        }

        return [
            'iso' => $date->format(\DateTimeInterface::ATOM),
            'label' => self::label($date, $now),
            'isToday' => $date->format('Y-m-d') === $now->format('Y-m-d'),
            'agendaUrl' => get_blog_permalink(Theme::ADMINISTRATION, self::AGENDA_PAGE_ID) ?: '/administration/',
            'liveUrl' => self::LIVE_URL,
        ];
    }

    private static function override(\DateTimeImmutable $now, \DateTimeZone $tz): ?\DateTimeImmutable
    {
        switch_to_blog(Theme::ADMINISTRATION);
        $value = trim((string)get_post_meta(self::AGENDA_PAGE_ID, self::OVERRIDE_META, true));
        restore_current_blog();

        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $value, $tz)
            ?: \DateTimeImmutable::createFromFormat('!Y-m-d', $value, $tz)?->setTime(self::HOUR, 0);

        if (!$date || $date->add(new \DateInterval(self::SESSION_LENGTH)) < $now) {
            return null;
        }

        return $date;
    }

    private static function computed(\DateTimeImmutable $now, \DateTimeZone $tz): ?\DateTimeImmutable
    {
        $month = $now->modify('first day of this month')->setTime(0, 0);

        for ($i = 0; $i < 13; $i++) {
            $candidate = $month->modify("+$i month");
            if (in_array((int)$candidate->format('n'), self::SKIPPED_MONTHS, true)) {
                continue;
            }
            $session = $candidate->modify('first monday of this month')->setTime(self::HOUR, 0);
            if ($session->add(new \DateInterval(self::SESSION_LENGTH)) >= $now) {
                return $session;
            }
        }

        return null;
    }

    /**
     * "Ce soir, 19h", "Demain, 19h" or "Lundi 7 décembre, 19h".
     */
    private static function label(\DateTimeImmutable $date, \DateTimeImmutable $now): string
    {
        $time = $date->format('G').'h'.($date->format('i') === '00' ? '' : $date->format('i'));
        $day = $date->format('Y-m-d');

        if ($day === $now->format('Y-m-d')) {
            return ((int)$date->format('G') >= 17 ? 'Ce soir' : 'Aujourd’hui').', '.$time;
        }
        if ($day === $now->modify('+1 day')->format('Y-m-d')) {
            return 'Demain, '.$time;
        }

        $formatter = new \IntlDateFormatter(
            'fr_BE',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            $date->getTimezone(),
            \IntlDateFormatter::GREGORIAN,
            'EEEE d MMMM',
        );

        $text = $formatter->format($date);
        if (!is_string($text) || $text === '') {
            return 'Le '.$date->format('d/m').', '.$time;
        }

        return mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1).', '.$time;
    }
}
