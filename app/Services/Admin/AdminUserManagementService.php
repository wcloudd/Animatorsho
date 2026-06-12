<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Support\IranianMobile;
use Illuminate\Validation\ValidationException;

class AdminUserManagementService
{
    /**
     * @param  array{
     *     name: string,
     *     username?: string|null,
     *     mobile?: string|null,
     *     verify_mobile?: bool
     * }  $validated
     * @return array{
     *     id: int,
     *     name: string,
     *     username: ?string,
     *     mobile: ?string,
     *     hasMobile: bool,
     *     mobileVerified: bool
     * }
     */
    public function update(User $user, array $validated): array
    {
        $name = trim($validated['name']);

        $username = $user->username;

        if (array_key_exists('username', $validated)) {
            $rawUsername = $validated['username'];

            if (is_string($rawUsername)) {
                $trimmedUsername = strtolower(trim($rawUsername));
                $username = $trimmedUsername === '' ? null : $trimmedUsername;
            } elseif ($rawUsername === null) {
                $username = null;
            }
        }

        $newMobile = $user->mobile;

        if (array_key_exists('mobile', $validated)) {
            $rawMobile = $validated['mobile'];

            if (is_string($rawMobile) && trim($rawMobile) !== '') {
                $normalizedMobile = IranianMobile::normalize($rawMobile);

                if ($normalizedMobile === null) {
                    throw ValidationException::withMessages([
                        'mobile' => IranianMobile::validationMessage($rawMobile),
                    ]);
                }

                $newMobile = $normalizedMobile;
            }
        }

        $verifyMobile = (bool) ($validated['verify_mobile'] ?? false);

        if ($verifyMobile && ! filled($newMobile)) {
            throw ValidationException::withMessages([
                'mobile' => 'برای تأیید موبایل، شماره موبایل لازم است.',
            ]);
        }

        $mobileVerifiedAt = $user->mobile_verified_at;

        if ($verifyMobile) {
            $mobileVerifiedAt = now();
        } elseif ($newMobile !== $user->mobile) {
            $mobileVerifiedAt = null;
        }

        $user->forceFill([
            'name' => $name,
            'username' => $username,
            'mobile' => $newMobile,
            'mobile_verified_at' => $mobileVerifiedAt,
        ])->save();

        return $this->summarize($user->fresh());
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     username: ?string,
     *     mobile: ?string,
     *     hasMobile: bool,
     *     mobileVerified: bool
     * }
     */
    public function summarize(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'mobile' => $user->mobile,
            'hasMobile' => filled($user->mobile),
            'mobileVerified' => $user->hasVerifiedMobile(),
        ];
    }
}
