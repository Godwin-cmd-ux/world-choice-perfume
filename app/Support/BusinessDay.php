<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * The shop's business day.
 *
 * Every "today" figure the staff sees is Dar es Salaam local time, so a day
 * has to roll over at 00:00 on the shop clock. The app itself runs on UTC,
 * where midnight falls three hours earlier, so daily totals computed with
 * Carbon::today() would keep yesterday's expenses on screen until 03:00 and
 * would drop the last three hours of the day. All daily boundaries go through
 * here instead.
 *
 * Dar es Salaam is UTC+3 with no daylight saving, but the conversions go
 * through Carbon anyway so the helper stays correct if the zone ever changes.
 *
 * `expenses.created_at` and `sales.created_at` are TIMESTAMP WITHOUT TIME
 * ZONE, written from `now()->toIso8601String()` and therefore holding UTC
 * wall-clock. PostgREST hands them back as bare `Y-m-d\TH:i:s` strings, so
 * that bare UTC form is what every bound and comparison below uses — it is
 * byte-comparable against the stored values.
 */
class BusinessDay
{
    public const TIMEZONE = 'Africa/Dar_es_Salaam';

    /** The shop clock right now. */
    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    /** 00:00:00 of the given day (today when omitted), shop clock. */
    public static function start(?Carbon $at = null): Carbon
    {
        return self::inZone($at)->startOfDay();
    }

    /** 23:59:59 of the given day (today when omitted), shop clock. */
    public static function end(?Carbon $at = null): Carbon
    {
        return self::inZone($at)->endOfDay();
    }

    /** Format any moment as a bare UTC stamp, e.g. 2026-09-29T21:00:00. */
    public static function stamp(Carbon $at): string
    {
        return $at->copy()->setTimezone('UTC')->format('Y-m-d\TH:i:s');
    }

    /** UTC stamp for the start of the day. */
    public static function startStamp(?Carbon $at = null): string
    {
        return self::stamp(self::start($at));
    }

    /** UTC stamp for the end of the day, i.e. 23:59:59 shop time. */
    public static function endStamp(?Carbon $at = null): string
    {
        return self::stamp(self::end($at));
    }

    /**
     * PostgREST takes a column filter of the form `gte.<from>,lte.<to>`, so a
     * whole day is one range on one round trip.
     */
    public static function dayFilter(?Carbon $at = null): string
    {
        return 'gte.' . self::startStamp($at) . ',lte.' . self::endStamp($at);
    }

    /** Same as dayFilter() but for an explicit start/end pair. */
    public static function rangeFilter(Carbon $from, Carbon $to): string
    {
        return 'gte.' . self::stamp($from) . ',lte.' . self::stamp($to);
    }

    /**
     * Build a PostgREST created_at filter from the optional <input type="date">
     * values on an expense screen. Those are local dates on the shop clock, so
     * the day is expanded to 00:00:00–23:59:59 local rather than compared as a
     * raw date. With neither bound the window is simply today, which is what
     * makes an unfiltered total fall to zero at midnight.
     */
    public static function localRangeFilter(?string $from, ?string $to): string
    {
        $start = ($from !== null && $from !== '')
            ? self::stamp(Carbon::parse($from, self::TIMEZONE)->startOfDay())
            : null;
        $end = ($to !== null && $to !== '')
            ? self::stamp(Carbon::parse($to, self::TIMEZONE)->endOfDay())
            : null;

        if ($start === null && $end === null) {
            return self::dayFilter();
        }
        if ($start === null) {
            return 'lte.' . $end;
        }

        return $end === null ? 'gte.' . $start : 'gte.' . $start . ',lte.' . $end;
    }

    /** Human label for the window localRangeFilter() produced. */
    public static function localRangeLabel(?string $from, ?string $to): string
    {
        $hasFrom = $from !== null && $from !== '';
        $hasTo = $to !== null && $to !== '';

        if (!$hasFrom && !$hasTo) {
            return 'Today (' . self::now()->format('M d, Y') . ')';
        }
        if ($hasFrom && $hasTo) {
            return Carbon::parse($from)->format('M d, Y') . ' – ' . Carbon::parse($to)->format('M d, Y');
        }

        return $hasFrom
            ? 'From ' . Carbon::parse($from)->format('M d, Y')
            : 'Up to ' . Carbon::parse($to)->format('M d, Y');
    }

    /** Is this created_at stamp inside the given business day (today when omitted)? */
    public static function withinDay(?string $createdAt, ?Carbon $at = null): bool
    {
        return $createdAt !== null
            && $createdAt >= self::startStamp($at)
            && $createdAt <= self::endStamp($at);
    }

    /** The local Y-m-d of a created_at stamp — what a <input type="date"> holds. */
    public static function localDate(?string $createdAt): string
    {
        if ($createdAt === null || $createdAt === '') {
            return '';
        }

        return Carbon::parse($createdAt, 'UTC')->setTimezone(self::TIMEZONE)->toDateString();
    }

    private static function inZone(?Carbon $at): Carbon
    {
        return ($at !== null ? $at->copy() : self::now())->setTimezone(self::TIMEZONE);
    }
}
