<?php

namespace App\Services\Admin;

use App\Concerns\UsernameValidationRules;
use App\DataTransferObjects\Admin\AdminUserLookupResult;
use App\Enums\AdminUserLookupPreviewStatus;
use App\Models\User;
use App\Support\IranianMobile;
use Illuminate\Validation\ValidationException;

class AdminUserLookupService
{
    use UsernameValidationRules;

    public const SUGGESTION_LIMIT = 8;

    public const USERNAME_NOT_FOUND_MESSAGE = 'کاربری با این نام کاربری پیدا نشد.';

    public const MOBILE_NOT_FOUND_MESSAGE = 'کاربری با این شماره پیدا نشد؛ در صورت ثبت، کاربر جدید ساخته می‌شود.';

    public const MOBILE_REQUIRED_MESSAGE = 'این کاربر شماره موبایل ثبت‌شده ندارد. برای ثبت دسترسی، شماره موبایل را وارد کنید.';

    public const MOBILE_ALREADY_USED_MESSAGE = 'این شماره موبایل قبلاً برای کاربر دیگری ثبت شده است.';

    public const LOOKUP_EMPTY_MESSAGE = 'برای بررسی، شماره موبایل یا نام کاربری را وارد کنید.';

    /**
     * Read-only lookup preview for admin manual enrollment.
     *
     * @return array{
     *     status: string,
     *     message: ?string,
     *     user: ?array{
     *         id: int,
     *         name: string,
     *         username: ?string,
     *         mobile: ?string,
     *         hasMobile: bool
     *     }
     * }
     */
    /**
     * Read-only autocomplete suggestions for admin manual enrollment.
     *
     * @return array{
     *     suggestions: list<array{
     *         id: int,
     *         name: string,
     *         username: ?string,
     *         mobile: ?string,
     *         hasMobile: bool,
     *         label: string
     *     }>
     * }
     */
    public function suggestions(?string $query): array
    {
        $lookup = $this->trimNullable($query);

        if ($lookup === null || $lookup === '') {
            return ['suggestions' => []];
        }

        if ($this->isMobileIdentifier($lookup)) {
            return $this->suggestByMobile($lookup);
        }

        if (mb_strlen($lookup) < 2) {
            return ['suggestions' => []];
        }

        return $this->suggestByUsernameOrName($lookup);
    }

    public function preview(?string $userLookup, ?string $customerMobile = null): array
    {
        $lookup = $this->trimNullable($userLookup);

        if ($lookup === null || $lookup === '') {
            return $this->emptyPreview();
        }

        if ($this->isMobileIdentifier($lookup)) {
            return $this->previewMobileLookup($lookup);
        }

        return $this->previewUsernameLookup($lookup);
    }

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
                $user = $this->findByMobile($lookup);
            } else {
                $user = $this->findByUsername($lookup);

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

    /**
     * @return array{
     *     status: string,
     *     message: ?string,
     *     user: ?array{
     *         id: int,
     *         name: string,
     *         username: ?string,
     *         mobile: ?string,
     *         hasMobile: bool
     *     }
     * }
     */
    private function previewMobileLookup(string $lookup): array
    {
        $normalizedMobile = IranianMobile::normalize($lookup);

        if ($normalizedMobile === null) {
            return $this->invalidPreview(IranianMobile::validationMessage($lookup));
        }

        $user = User::query()->where('mobile', $normalizedMobile)->first();

        if (! $user instanceof User) {
            return [
                'status' => AdminUserLookupPreviewStatus::NotFound->value,
                'message' => self::MOBILE_NOT_FOUND_MESSAGE,
                'user' => null,
            ];
        }

        return $this->previewForUser($user);
    }

    /**
     * @return array{
     *     status: string,
     *     message: ?string,
     *     user: ?array{
     *         id: int,
     *         name: string,
     *         username: ?string,
     *         mobile: ?string,
     *         hasMobile: bool
     *     }
     * }
     */
    private function previewUsernameLookup(string $lookup): array
    {
        $normalizedUsername = strtolower($lookup);

        $usernameValidator = validator(
            ['user_lookup' => $normalizedUsername],
            ['user_lookup' => $this->usernameFormatRules(required: true)],
        );

        if ($usernameValidator->fails()) {
            return $this->invalidPreview($usernameValidator->errors()->first('user_lookup'));
        }

        $user = $this->findByUsername($normalizedUsername);

        if (! $user instanceof User) {
            return [
                'status' => AdminUserLookupPreviewStatus::NotFound->value,
                'message' => self::USERNAME_NOT_FOUND_MESSAGE,
                'user' => null,
            ];
        }

        return $this->previewForUser($user);
    }

    /**
     * @return array{
     *     status: string,
     *     message: ?string,
     *     user: ?array{
     *         id: int,
     *         name: string,
     *         username: ?string,
     *         mobile: ?string,
     *         hasMobile: bool
     *     }
     * }
     */
    private function previewForUser(User $user): array
    {
        $summary = $this->summarizeUser($user);

        if (! $summary['hasMobile']) {
            return [
                'status' => AdminUserLookupPreviewStatus::NeedsMobile->value,
                'message' => self::MOBILE_REQUIRED_MESSAGE,
                'user' => $summary,
            ];
        }

        return [
            'status' => AdminUserLookupPreviewStatus::Found->value,
            'message' => 'کاربر پیدا شد',
            'user' => $summary,
        ];
    }

    /**
     * @return array{
     *     status: string,
     *     message: ?string,
     *     user: null
     * }
     */
    private function emptyPreview(): array
    {
        return [
            'status' => AdminUserLookupPreviewStatus::Empty->value,
            'message' => self::LOOKUP_EMPTY_MESSAGE,
            'user' => null,
        ];
    }

    /**
     * @return array{
     *     status: string,
     *     message: string,
     *     user: null
     * }
     */
    private function invalidPreview(string $message): array
    {
        return [
            'status' => AdminUserLookupPreviewStatus::Invalid->value,
            'message' => $message,
            'user' => null,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     username: ?string,
     *     mobile: ?string,
     *     hasMobile: bool
     * }
     */
    /**
     * @return array{
     *     suggestions: list<array{
     *         id: int,
     *         name: string,
     *         username: ?string,
     *         mobile: ?string,
     *         hasMobile: bool,
     *         label: string
     *     }>
     * }
     */
    private function suggestByMobile(string $lookup): array
    {
        $digits = preg_replace('/\D+/', '', $lookup);

        if ($digits === null || strlen($digits) < 4) {
            return ['suggestions' => []];
        }

        if (str_starts_with($digits, '98')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '9') && ! str_starts_with($digits, '09')) {
            $searchDigits = '0'.$digits;
        } else {
            $searchDigits = $digits;
        }

        $users = User::query()
            ->whereNotNull('mobile')
            ->where('mobile', 'like', '%'.$searchDigits.'%')
            ->orderBy('mobile')
            ->limit(self::SUGGESTION_LIMIT)
            ->get();

        return [
            'suggestions' => $users
                ->map(fn (User $user): array => $this->suggestionItem($user))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     suggestions: list<array{
     *         id: int,
     *         name: string,
     *         username: ?string,
     *         mobile: ?string,
     *         hasMobile: bool,
     *         label: string
     *     }>
     * }
     */
    private function suggestByUsernameOrName(string $lookup): array
    {
        $normalizedQuery = strtolower($lookup);
        $foundIds = [];
        $users = collect();

        $exactMatches = User::query()
            ->where('username', $normalizedQuery)
            ->get();

        foreach ($exactMatches as $user) {
            $users->push($user);
            $foundIds[] = $user->id;
        }

        if ($users->count() < self::SUGGESTION_LIMIT) {
            $prefixMatches = User::query()
                ->where('username', 'like', $normalizedQuery.'%')
                ->whereNotIn('id', $foundIds)
                ->orderBy('username')
                ->limit(self::SUGGESTION_LIMIT - $users->count())
                ->get();

            foreach ($prefixMatches as $user) {
                $users->push($user);
                $foundIds[] = $user->id;
            }
        }

        if ($users->count() < self::SUGGESTION_LIMIT) {
            $nameMatches = User::query()
                ->where('name', 'like', '%'.$lookup.'%')
                ->whereNotIn('id', $foundIds)
                ->orderBy('name')
                ->limit(self::SUGGESTION_LIMIT - $users->count())
                ->get();

            foreach ($nameMatches as $user) {
                $users->push($user);
            }
        }

        return [
            'suggestions' => $users
                ->take(self::SUGGESTION_LIMIT)
                ->map(fn (User $user): array => $this->suggestionItem($user))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     username: ?string,
     *     mobile: ?string,
     *     hasMobile: bool,
     *     label: string
     * }
     */
    private function suggestionItem(User $user): array
    {
        $summary = $this->summarizeUser($user);

        return [
            ...$summary,
            'label' => $this->suggestionLabel($user),
        ];
    }

    private function suggestionLabel(User $user): string
    {
        $label = $user->name;

        if (filled($user->username)) {
            $label .= ' (@'.$user->username.')';
        }

        if (filled($user->mobile)) {
            $label .= ' · '.$user->mobile;
        }

        return $label;
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     username: ?string,
     *     mobile: ?string,
     *     hasMobile: bool
     * }
     */
    private function summarizeUser(User $user): array
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

    private function findByMobile(string $lookup): ?User
    {
        $normalizedMobile = IranianMobile::normalize($lookup);

        if ($normalizedMobile === null) {
            return null;
        }

        return User::query()->where('mobile', $normalizedMobile)->first();
    }

    private function findByUsername(string $lookup): ?User
    {
        return User::query()->where('username', strtolower($lookup))->first();
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
