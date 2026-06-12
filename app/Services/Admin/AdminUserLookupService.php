<?php

namespace App\Services\Admin;

use App\DataTransferObjects\Admin\AdminUserLookupResult;
use App\Models\User;
use App\Support\IranianMobile;
use Illuminate\Validation\ValidationException;

class AdminUserLookupService
{
    public const USERNAME_NOT_FOUND_MESSAGE = 'کاربری با این نام کاربری پیدا نشد.';

    public const MOBILE_REQUIRED_MESSAGE = 'این کاربر شماره موبایل ثبت‌شده ندارد. برای ثبت دسترسی، شماره موبایل را وارد کنید.';

    public const MOBILE_ALREADY_USED_MESSAGE = 'این شماره موبایل قبلاً برای کاربر دیگری ثبت شده است.';

    /**
     * Resolve an existing user by mobile/username lookup, or create a new user when a valid mobile is available.
     *
     * When both user_lookup and customer_mobile look like mobile numbers, user_lookup takes precedence for resolution.
     */
    public function resolve(string $customerName, ?string $userLookup, ?string $customerMobile): AdminUserLookupResult
    {
        $lookup = $this->trimNullable($userLookup);
        $normalizedCustomerMobile = IranianMobile::normalize($this->trimNullable($customerMobile) ?? '');

        $user = null;
        $created = false;

        if ($lookup !== null && $lookup !== '') {
            if ($this->isMobileIdentifier($lookup)) {
                $normalizedLookupMobile = IranianMobile::normalize($lookup);

                if ($normalizedLookupMobile !== null) {
                    $user = User::query()->where('mobile', $normalizedLookupMobile)->first();
                }
            } else {
                $user = User::query()->where('username', strtolower($lookup))->first();

                if ($user === null) {
                    throw ValidationException::withMessages([
                        'user_lookup' => self::USERNAME_NOT_FOUND_MESSAGE,
                    ]);
                }
            }
        }

        if ($user === null) {
            $mobileForResolution = $this->mobileForFindOrCreate($lookup, $normalizedCustomerMobile);

            if ($mobileForResolution === null) {
                throw ValidationException::withMessages([
                    'customer_mobile' => IranianMobile::EMPTY_MESSAGE,
                ]);
            }

            $existingUser = User::query()->where('mobile', $mobileForResolution)->first();

            if ($existingUser instanceof User) {
                $user = $existingUser;
            } else {
                $user = User::query()->create([
                    'name' => trim($customerName),
                    'mobile' => $mobileForResolution,
                    'username' => null,
                    'password' => null,
                    'mobile_verified_at' => null,
                ]);
                $created = true;
            }
        }

        $orderCustomerMobile = $this->resolveOrderCustomerMobile($user, $normalizedCustomerMobile);

        return new AdminUserLookupResult(
            user: $user,
            created: $created,
            orderCustomerMobile: $orderCustomerMobile,
        );
    }

    private function resolveOrderCustomerMobile(User $user, ?string $normalizedCustomerMobile): string
    {
        if (filled($user->mobile)) {
            return (string) $user->mobile;
        }

        if ($normalizedCustomerMobile === null) {
            throw ValidationException::withMessages([
                'customer_mobile' => self::MOBILE_REQUIRED_MESSAGE,
            ]);
        }

        $mobileTaken = User::query()
            ->where('mobile', $normalizedCustomerMobile)
            ->whereKeyNot($user->id)
            ->exists();

        if ($mobileTaken) {
            throw ValidationException::withMessages([
                'customer_mobile' => self::MOBILE_ALREADY_USED_MESSAGE,
            ]);
        }

        $user->forceFill(['mobile' => $normalizedCustomerMobile])->save();

        return $normalizedCustomerMobile;
    }

    /**
     * Prefer user_lookup mobile over customer_mobile when both are present.
     */
    private function mobileForFindOrCreate(?string $lookup, ?string $normalizedCustomerMobile): ?string
    {
        if ($lookup !== null && $lookup !== '' && $this->isMobileIdentifier($lookup)) {
            $normalizedLookupMobile = IranianMobile::normalize($lookup);

            if ($normalizedLookupMobile !== null) {
                return $normalizedLookupMobile;
            }
        }

        return $normalizedCustomerMobile;
    }

    private function isMobileIdentifier(string $value): bool
    {
        if (IranianMobile::looksLikeMobileAttempt($value)) {
            return true;
        }

        return IranianMobile::normalize($value) !== null;
    }

    private function trimNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
