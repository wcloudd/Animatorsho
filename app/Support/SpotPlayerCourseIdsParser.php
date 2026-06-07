<?php

namespace App\Support;

class SpotPlayerCourseIdsParser
{
    /**
     * @return list<string>
     */
    public static function parse(?string $input): array
    {
        if ($input === null || trim($input) === '') {
            return [];
        }

        $parts = preg_split('/[\s,]+/', trim($input)) ?: [];

        $ids = [];

        foreach ($parts as $part) {
            $id = trim($part);

            if ($id === '') {
                continue;
            }

            if (! preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
                continue;
            }

            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<string>|null  $ids
     */
    public static function toAdminText(?array $ids): string
    {
        if ($ids === null || $ids === []) {
            return '';
        }

        return implode("\n", $ids);
    }
}
