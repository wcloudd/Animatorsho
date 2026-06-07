<?php

namespace App\Support\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminListSearch
{
    public static function normalize(?string $term): ?string
    {
        if ($term === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($term));

        if (! is_string($normalized) || mb_strlen($normalized) < 2) {
            return null;
        }

        return $normalized;
    }

    public static function likePattern(string $term): string
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return '%'.$escaped.'%';
    }

    /**
     * @param  Builder<Model>  $query
     * @param  callable(Builder<Model>): void  $callback
     */
    public static function apply(Builder $query, ?string $term, callable $callback): void
    {
        $normalized = self::normalize($term);

        if ($normalized === null) {
            return;
        }

        $query->where(function (Builder $searchQuery) use ($normalized, $callback): void {
            $callback($searchQuery, self::likePattern($normalized), $normalized);
        });
    }

    /**
     * @param  array<string, string>  $keywordMap
     */
    public static function matchesEnumKeyword(string $term, array $keywordMap): ?string
    {
        $lower = mb_strtolower($term);

        foreach ($keywordMap as $keyword => $value) {
            if (str_contains($lower, mb_strtolower($keyword))) {
                return $value;
            }
        }

        return null;
    }
}
