<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\AvatarPresetRegistry;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'avatar_preset', 'email', 'password', 'mobile', 'mobile_verified_at', 'is_admin'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    public function hasEmail(): bool
    {
        return filled($this->email);
    }

    public function hasVerifiedMobile(): bool
    {
        return filled($this->mobile) && $this->mobile_verified_at !== null;
    }

    public function validAvatarPreset(): ?string
    {
        if (! AvatarPresetRegistry::isValid($this->avatar_preset)) {
            return null;
        }

        return $this->avatar_preset;
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

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }
}
