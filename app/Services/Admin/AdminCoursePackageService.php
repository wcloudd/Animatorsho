<?php

namespace App\Services\Admin;

use App\Models\CoursePackage;
use App\Support\SpotPlayerAccessLimitNormalizer;
use App\Support\SpotPlayerCourseIdsParser;
use App\Support\TomanFormatter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminCoursePackageService
{
    /**
     * @return array{
     *     packages: LengthAwarePaginator<int, array{
     *         id: int,
     *         title: string,
     *         slug: string,
     *         priceToman: int,
     *         priceFormatted: string,
     *         isActive: bool,
     *         displayOrder: int,
     *         ordersCount: int
     *     }>
     * }
     */
    public function listForAdmin(): array
    {
        $packages = CoursePackage::query()
            ->withCount('orders')
            ->orderBy('display_order')
            ->orderBy('id')
            ->paginate(20)
            ->through(fn (CoursePackage $package): array => $this->toListItem($package));

        return [
            'packages' => $packages,
        ];
    }

    /**
     * @return array{
     *     package: array{
     *         id: int,
     *         title: string,
     *         slug: string,
     *         priceToman: int,
     *         isActive: bool,
     *         displayOrder: int,
     *         ordersCount: int,
     *         spotplayerCourseIdsText: string,
     *         spotplayerAccessLimit: string | null
     *     }
     * }
     */
    public function toEditProps(CoursePackage $package): array
    {
        $package->loadCount('orders');

        return [
            'package' => [
                'id' => $package->id,
                'title' => $package->title,
                'slug' => $package->slug,
                'priceToman' => $package->price_toman,
                'isActive' => $package->is_active,
                'displayOrder' => $package->display_order,
                'ordersCount' => $package->orders_count,
                'spotplayerCourseIdsText' => SpotPlayerCourseIdsParser::toAdminText($package->spotplayer_course_ids),
                'spotplayerAccessLimit' => $package->spotplayer_access_limit,
            ],
        ];
    }

    /**
     * @param  array{
     *     title: string,
     *     price_toman: int,
     *     is_active: bool,
     *     display_order: int,
     *     spotplayer_course_ids_input?: string|null,
     *     spotplayer_access_limit?: string|null
     * }  $data
     */
    public function update(CoursePackage $package, array $data): CoursePackage
    {
        $courseIds = SpotPlayerCourseIdsParser::parse($data['spotplayer_course_ids_input'] ?? null);

        $package->update([
            'title' => $data['title'],
            'price_toman' => $data['price_toman'],
            'is_active' => $data['is_active'],
            'display_order' => $data['display_order'],
            'spotplayer_course_ids' => $courseIds === [] ? null : $courseIds,
            'spotplayer_access_limit' => SpotPlayerAccessLimitNormalizer::normalize($data['spotplayer_access_limit'] ?? null),
        ]);

        return $package->fresh();
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     slug: string,
     *     priceToman: int,
     *     priceFormatted: string,
     *     isActive: bool,
     *     displayOrder: int,
     *     ordersCount: int
     * }
     */
    private function toListItem(CoursePackage $package): array
    {
        return [
            'id' => $package->id,
            'title' => $package->title,
            'slug' => $package->slug,
            'priceToman' => $package->price_toman,
            'priceFormatted' => TomanFormatter::format($package->price_toman),
            'isActive' => $package->is_active,
            'displayOrder' => $package->display_order,
            'ordersCount' => $package->orders_count,
        ];
    }
}
