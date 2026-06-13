<?php

namespace App\Models;

use Database\Factories\CourseResourceCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'slug',
    'description',
    'display_order',
    'is_active',
])]
class CourseResourceCategory extends Model
{
    /** @use HasFactory<CourseResourceCategoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CourseResource, $this>
     */
    public function resources(): HasMany
    {
        return $this->hasMany(CourseResource::class);
    }
}
