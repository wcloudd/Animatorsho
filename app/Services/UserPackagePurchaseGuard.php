<?php

namespace App\Services;

use App\Models\CoursePackage;
use App\Models\User;
use App\Support\ProfileAccessPresenter;

class UserPackagePurchaseGuard
{
    public const string BLOCKING_MESSAGE = 'شما قبلاً برای این دوره ثبت‌نام یا درخواست فعال دارید. وضعیت آن را از پروفایل پیگیری کنید.';

    public function __construct(
        private readonly ProfileAccessPresenter $accessPresenter,
    ) {}

    public function hasBlockingAccess(User $user, CoursePackage $package): bool
    {
        $orders = $user->orders()
            ->where('course_package_id', $package->id)
            ->with(['coursePackage', 'payments', 'spotPlayerLicense'])
            ->get();

        $licenses = $user->spotPlayerLicenses()
            ->where('course_package_id', $package->id)
            ->with('coursePackage')
            ->get();

        return $this->accessPresenter->hasBlockingPackageAccess(
            $orders,
            $licenses,
            $package->id,
        );
    }

    public function message(): string
    {
        return self::BLOCKING_MESSAGE;
    }
}
