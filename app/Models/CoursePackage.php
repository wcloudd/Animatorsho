<?php

namespace App\Models;

use App\Enums\CoursePackageType;
use Database\Factories\CoursePackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id',
    'title',
    'slug',
    'type',
    'chapter_number',
    'description',
    'price_toman',
    'is_active',
    'display_order',
    'spotplayer_course_ids',
])]
class CoursePackage extends Model
{
    /** @use HasFactory<CoursePackageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CoursePackageType::class,
            'price_toman' => 'integer',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'chapter_number' => 'integer',
            'spotplayer_course_ids' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<SpotPlayerLicense, $this>
     */
    public function spotPlayerLicenses(): HasMany
    {
        return $this->hasMany(SpotPlayerLicense::class);
    }
}
