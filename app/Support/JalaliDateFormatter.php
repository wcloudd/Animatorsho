<?php

namespace App\Support;

use Carbon\CarbonInterface;

class JalaliDateFormatter
{
    /**
     * @var array<int, string>
     */
    private const PERSIAN_MONTHS = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];

    public static function publishedAtLabel(?CarbonInterface $publishedAt): string
    {
        if ($publishedAt === null) {
            return '—';
        }

        $publishedAt = $publishedAt->timezone(config('app.timezone'));

        [$year, $month, $day] = self::gregorianToJalali(
            (int) $publishedAt->format('Y'),
            (int) $publishedAt->format('n'),
            (int) $publishedAt->format('j'),
        );

        return self::toPersianDigits(sprintf(
            '%d %s %d',
            $day,
            self::PERSIAN_MONTHS[$month],
            $year,
        ));
    }

    public static function publishedAtLabelWithTime(?CarbonInterface $publishedAt): string
    {
        if ($publishedAt === null) {
            return '—';
        }

        $dateLabel = self::publishedAtLabel($publishedAt);

        if ($dateLabel === '—') {
            return '—';
        }

        $timeLabel = self::toPersianDigits(
            $publishedAt->timezone(config('app.timezone'))->format('H:i'),
        );

        return sprintf('%s، %s', $dateLabel, $timeLabel);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function gregorianToJalali(int $year, int $month, int $day): array
    {
        $monthDayOffsets = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gregorianLeapYear = ($month > 2) ? ($year + 1) : $year;
        $dayCount = 355666
            + (365 * $year)
            + intdiv($gregorianLeapYear + 3, 4)
            - intdiv($gregorianLeapYear + 99, 100)
            + intdiv($gregorianLeapYear + 399, 400)
            + $day
            + $monthDayOffsets[$month - 1];

        $jalaliYear = -1595 + (33 * intdiv($dayCount, 12053));
        $dayCount %= 12053;
        $jalaliYear += 4 * intdiv($dayCount, 1461);
        $dayCount %= 1461;

        if ($dayCount > 365) {
            $jalaliYear += intdiv($dayCount - 1, 365);
            $dayCount = ($dayCount - 1) % 365;
        }

        if ($dayCount < 186) {
            $jalaliMonth = 1 + intdiv($dayCount, 31);
            $jalaliDay = 1 + ($dayCount % 31);
        } else {
            $jalaliMonth = 7 + intdiv($dayCount - 186, 30);
            $jalaliDay = 1 + (($dayCount - 186) % 30);
        }

        return [$jalaliYear, $jalaliMonth, $jalaliDay];
    }

    private static function toPersianDigits(string $value): string
    {
        return strtr($value, [
            '0' => '۰',
            '1' => '۱',
            '2' => '۲',
            '3' => '۳',
            '4' => '۴',
            '5' => '۵',
            '6' => '۶',
            '7' => '۷',
            '8' => '۸',
            '9' => '۹',
        ]);
    }
}
