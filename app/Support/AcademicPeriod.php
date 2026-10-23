<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class AcademicPeriod
{
    public const GANJIL = 'ganjil';

    public const GENAP = 'genap';

    public const ALL = 'all';

    /**
     * @return array<int, string>
     */
    public static function semesters(): array
    {
        return [self::ALL, self::GANJIL, self::GENAP];
    }

    public static function normalizeSemester(?string $semester): string
    {
        return in_array($semester, [self::GANJIL, self::GENAP], true) ? $semester : self::ALL;
    }

    public static function label(int $startYear): string
    {
        return sprintf('%d/%d', $startYear, $startYear + 1);
    }

    public static function startYear(?string $label): ?int
    {
        if ($label === null || ! preg_match('/^\s*(\d{4})\s*\/\s*(\d{4})\s*$/', $label, $matches)) {
            return null;
        }

        $start = (int) $matches[1];
        $end = (int) $matches[2];

        if ($end !== $start + 1 || $start < 2000 || $start > 2100) {
            return null;
        }

        return $start;
    }

    /**
     * @return array<int, int>
     */
    public static function startYears(?string $label): array
    {
        $start = self::startYear($label);

        return $start === null ? [] : [$start, $start + 1];
    }

    public static function currentSemester(?Carbon $date = null): string
    {
        $month = (int) ($date ?? now())->format('n');

        return $month >= 9 || $month <= 2 ? self::GANJIL : self::GENAP;
    }

    public static function current(?Carbon $date = null): string
    {
        $date ??= now();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        if ($month >= 9) {
            return self::label($year);
        }

        if ($month <= 2) {
            return self::label($year - 1);
        }

        return self::label($year - 1);
    }

    /**
     * Build TA options from rows that carry both start_year and semester.
     *
     * Semester genap belongs to the previous academic year, so both columns
     * are required to avoid collapsing two academic years into one label.
     *
     * @param  iterable<array{start_year?: int|string|null, semester?: string|null}|object{start_year?: int|string|null, semester?: string|null}>  $periods
     * @return array<int, string>
     */
    public static function optionsFromPeriods(iterable $periods): array
    {
        $labels = [];

        foreach ($periods as $period) {
            if (is_array($period)) {
                $startYear = $period['start_year'] ?? null;
                $semester = $period['semester'] ?? null;
            } else {
                $startYear = $period->start_year ?? null;
                $semester = $period->semester ?? null;
            }

            if ($startYear === null || $startYear === '') {
                continue;
            }

            $academicStart = $semester === self::GENAP ? (int) $startYear - 1 : (int) $startYear;

            $labels[self::label($academicStart)] = true;
        }

        $options = array_keys($labels);
        rsort($options);

        return $options;
    }

    /**
     * Build TA options from any collection of start_year values.
     *
     * @param  iterable<int|string|null>  $startYears
     * @return array<int, string>
     */
    public static function optionsFromStartYears(iterable $startYears): array
    {
        $labels = [];

        foreach ($startYears as $startYear) {
            if ($startYear === null || $startYear === '') {
                continue;
            }

            $labels[self::label((int) $startYear)] = true;
        }

        $options = array_keys($labels);
        rsort($options);

        return $options;
    }
}
