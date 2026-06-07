<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use Database\Factories\OtpCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'mobile',
    'code_hash',
    'purpose',
    'expires_at',
    'consumed_at',
    'attempts',
    'ip_address',
    'user_agent',
])]
class OtpCode extends Model
{
    /** @use HasFactory<OtpCodeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * @param  Builder<OtpCode>  $query
     */
    public function scopeForMobile(Builder $query, string $mobile, OtpPurpose $purpose): Builder
    {
        return $query
            ->where('mobile', $mobile)
            ->where('purpose', $purpose);
    }

    /**
     * @param  Builder<OtpCode>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= (int) config('otp.max_attempts', 5);
    }
}
