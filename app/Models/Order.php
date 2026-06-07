<?php

namespace App\Models;

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'course_package_id',
    'order_number',
    'status',
    'payment_type',
    'amount_toman',
    'final_amount_toman',
    'customer_name',
    'customer_mobile',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_type' => OrderPaymentType::class,
            'amount_toman' => 'integer',
            'final_amount_toman' => 'integer',
        ];
    }

    public static function generateOrderNumber(): string
    {
        do {
            $suffix = Str::upper(Str::random(6));
            $orderNumber = 'AS-'.now()->format('Ymd').'-'.$suffix;
        } while (self::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Snapshot catalog price onto the order so later package edits do not rewrite history.
     */
    public function snapshotAmountsFromPackage(?CoursePackage $package = null): void
    {
        $package ??= $this->coursePackage;

        if ($package === null) {
            return;
        }

        $this->amount_toman = $package->price_toman;
        $this->final_amount_toman = $package->price_toman;
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
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<SpotPlayerLicense, $this>
     */
    public function spotPlayerLicense(): HasOne
    {
        return $this->hasOne(SpotPlayerLicense::class);
    }
}
