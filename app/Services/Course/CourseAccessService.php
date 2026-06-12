<?php

namespace App\Services\Course;

use App\Enums\CoursePackageType;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Services\AnimatorshoCatalogService;
use App\Support\ProfileAccessPresenter;
use Illuminate\Support\Collection;

class CourseAccessService
{
    /** @var Collection<int, int>|null */
    private ?Collection $animatorshoPackageIds = null;

    public function __construct(
        private readonly AnimatorshoCatalogService $catalog,
        private readonly ProfileAccessPresenter $accessPresenter,
    ) {}

    public function userHasActiveAccess(User $user): bool
    {
        $packageIds = $this->animatorshoPackageIds();

        if ($packageIds->isEmpty()) {
            return false;
        }

        return $user->spotPlayerLicenses()
            ->whereIn('course_package_id', $packageIds)
            ->where('status', SpotPlayerLicenseStatus::Active)
            ->exists();
    }

    /**
     * @return array{
     *     welcome: array{displayName: string, hasFullAccess: bool},
     *     chapters: list<array{
     *         slug: string,
     *         title: string,
     *         chapterNumber: int|null,
     *         isAccessible: bool,
     *         accessLabel: ?string
     *     }>,
     *     spotPlayerLicenses: list<array{
     *         packageTitle: string,
     *         licenseKey: string,
     *         isFullPackage: bool
     *     }>
     * }
     */
    public function courseHomePropsForUser(User $user): array
    {
        $packages = $this->catalog->activePackages();
        $packageIds = $packages->pluck('id');

        $orders = $user->orders()
            ->whereIn('course_package_id', $packageIds)
            ->with([
                'coursePackage',
                'payments' => fn ($query) => $query->latest(),
                'spotPlayerLicense',
            ])
            ->get();

        $licenses = $user->spotPlayerLicenses()
            ->whereIn('course_package_id', $packageIds)
            ->with('coursePackage')
            ->get();

        $fullPackage = $packages->firstWhere(
            'slug',
            AnimatorshoCatalogService::FULL_PACKAGE_SLUG,
        );

        $hasFullAccess = $fullPackage !== null
            && $this->accessPresenter->accessStateForPackage($orders, $licenses, $fullPackage->id) === 'access_active';

        $chapters = $packages
            ->where('type', CoursePackageType::Chapter)
            ->values()
            ->map(function (CoursePackage $package) use ($orders, $licenses, $hasFullAccess): array {
                $isChapterActive = $this->accessPresenter->accessStateForPackage(
                    $orders,
                    $licenses,
                    $package->id,
                ) === 'access_active';

                $isAccessible = $hasFullAccess || $isChapterActive;

                return [
                    'slug' => $package->slug,
                    'title' => $package->title,
                    'chapterNumber' => $package->chapter_number,
                    'isAccessible' => $isAccessible,
                    'accessLabel' => $hasFullAccess
                        ? 'دسترسی کامل'
                        : ($isChapterActive ? 'دسترسی فعال' : null),
                ];
            })
            ->all();

        $spotPlayerLicenses = [];

        foreach ($packages as $package) {
            if ($this->accessPresenter->accessStateForPackage($orders, $licenses, $package->id) !== 'access_active') {
                continue;
            }

            $licenseKey = $this->resolveActiveLicenseKey($orders, $licenses, $package->id);

            if ($licenseKey === null) {
                continue;
            }

            $spotPlayerLicenses[] = [
                'packageTitle' => $package->title,
                'licenseKey' => $licenseKey,
                'isFullPackage' => $package->type === CoursePackageType::FullCourse,
            ];
        }

        return [
            'welcome' => [
                'displayName' => $user->name,
                'hasFullAccess' => $hasFullAccess,
            ],
            'chapters' => $chapters,
            'spotPlayerLicenses' => $spotPlayerLicenses,
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @param  Collection<int, SpotPlayerLicense>  $licenses
     * @return Collection<int, int>
     */
    private function animatorshoPackageIds(): Collection
    {
        if ($this->animatorshoPackageIds !== null) {
            return $this->animatorshoPackageIds;
        }

        $this->animatorshoPackageIds = $this->catalog
            ->activePackages()
            ->pluck('id');

        return $this->animatorshoPackageIds;
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @param  Collection<int, SpotPlayerLicense>  $licenses
     */
    private function resolveActiveLicenseKey(
        Collection $orders,
        Collection $licenses,
        int $packageId,
    ): ?string {
        $license = $licenses
            ->where('course_package_id', $packageId)
            ->first(fn (SpotPlayerLicense $license): bool => $license->status === SpotPlayerLicenseStatus::Active);

        if ($license?->license_key !== null) {
            return $license->license_key;
        }

        foreach ($orders->where('course_package_id', $packageId) as $order) {
            $orderLicense = $order->spotPlayerLicense;

            if (
                $orderLicense?->status === SpotPlayerLicenseStatus::Active
                && $orderLicense->license_key !== null
            ) {
                return $orderLicense->license_key;
            }
        }

        return null;
    }
}
