<?php

namespace App\Models;

use App\Enums\SpotPlayerLicenseStatus;
use Database\Factories\SpotPlayerLicenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'course_package_id',
    'order_id',
    'license_key',
    'status',
    'activated_at',
    'meta',
])]
class SpotPlayerLicense extends Model
{
    /** @use HasFactory<SpotPlayerLicenseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SpotPlayerLicenseStatus::class,
            'activated_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<CoursePackage, $this>
     */
    public function coursePackage(): BelongsTo
    {
        return $this->belongsTo(CoursePackage::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
