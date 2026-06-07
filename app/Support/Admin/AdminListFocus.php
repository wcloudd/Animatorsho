<?php

namespace App\Support\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminListFocus
{
    public static function normalize(?int $focus): ?int
    {
        if ($focus === null || $focus <= 0) {
            return null;
        }

        return $focus;
    }

    /**
     * @param  Builder<Model>  $query
     */
    public static function apply(Builder $query, ?int $focus): void
    {
        if ($focus === null) {
            return;
        }

        $query->where($query->getModel()->getQualifiedKeyName(), $focus);
    }
}
