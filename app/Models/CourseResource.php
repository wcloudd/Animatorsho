<?php

namespace App\Models;

use App\Enums\CourseResourceAccessScope;
use App\Enums\CourseResourceStatus;
use App\Enums\CourseResourceType;
use Database\Factories\CourseResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'course_resource_category_id',
    'title',
    'description',
    'type',
    'file_path',
    'external_url',
    'status',
    'access_scope',
    'course_package_id',
    'display_order',
    'published_at',
])]
class CourseResource extends Model
{
    /** @use HasFactory<CourseResourceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CourseResourceType::class,
            'status' => CourseResourceStatus::class,
            'access_scope' => CourseResourceAccessScope::class,
            'display_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CourseResourceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseResourceCategory::class, 'course_resource_category_id');
    }

    /**
     * @return BelongsTo<CoursePackage, $this>
     */
    public function coursePackage(): BelongsTo
    {
        return $this->belongsTo(CoursePackage::class);
    }
}
