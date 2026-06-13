<?php

namespace App\Models;

use App\Enums\CourseUpdateStatus;
use App\Enums\CourseUpdateType;
use App\Enums\CourseUpdateVisualTheme;
use Database\Factories\CourseUpdateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'summary',
    'body',
    'type',
    'visual_theme',
    'status',
    'is_pinned',
    'display_order',
    'published_at',
])]
class CourseUpdate extends Model
{
    /** @use HasFactory<CourseUpdateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CourseUpdateType::class,
            'visual_theme' => CourseUpdateVisualTheme::class,
            'status' => CourseUpdateStatus::class,
            'is_pinned' => 'boolean',
            'display_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }
}
