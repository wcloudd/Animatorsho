<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\IranianMobile;
use App\Support\ProfileAccessPresenter;
use App\Support\ProfileStatusLabels;
use Illuminate\Support\Collection;

class ProfileDashboardService
{
    public function __construct(
        private readonly ProfileAccessPresenter $accessPresenter,
    ) {}

    /**
     * @return array{
     *     user: array{
     *         displayName: string,
     *         email: ?string,
     *         avatarPreset: ?string,
     *         maskedMobile: ?string,
     *         settingsUrl: string
     *     },
     *     accessItems: list<array{
     *         id: string,
     *         packageId: int,
     *         orderId: ?int,
     *         licenseId: ?int,
     *         title: string,
     *         accessState: string,
     *         statusLabel: string,
     *         statusTone: string,
     *         description: string,
     *         paymentMethod: ?string,
     *         amountToman: ?int,
     *         licenseKey: ?string,
     *         rejectionReason: ?string,
     *         nextAction: ?array{label: string, href: string, external: bool}
     *     }>,
     *     orderHistory: list<array{
     *         id: int,
     *         orderNumber: string,
     *         title: string,
     *         status: string,
     *         statusTone: string,
     *         paymentType: string,
     *         paymentMethod: ?string,
     *         amountToman: int,
     *         createdAt: ?string
     *     }>,
     *     hasOrderHistory: bool,
     *     accessLinks: array{
     *         spotplayerInstallGuideUrl: ?string,
     *         studentGroupUrl: ?string
     *     }
     * }
     */
    public function forUser(User $user): array
    {
        $orders = $user->orders()
            ->with([
                'coursePackage',
                'payments' => fn ($query) => $query->latest(),
                'spotPlayerLicense',
            ])
            ->latest()
            ->get();

        $licenses = $user->spotPlayerLicenses()
            ->with('coursePackage')
            ->latest()
            ->get();

        return [
            'user' => [
                'displayName' => $user->name,
                'email' => $user->email,
                'avatarPreset' => $user->validAvatarPreset(),
                'maskedMobile' => IranianMobile::mask($user->mobile),
                'settingsUrl' => route('profile.settings'),
            ],
            'accessItems' => $this->accessPresenter->present($orders, $licenses),
            'orderHistory' => $this->mapOrderHistory($orders),
            'hasOrderHistory' => $orders->isNotEmpty(),
            'accessLinks' => [
                'spotplayerInstallGuideUrl' => $this->nullableConfigUrl('student_panel.profile.spotplayerInstallGuideUrl'),
                'studentGroupUrl' => $this->nullableConfigUrl('student_panel.profile.studentGroupUrl'),
            ],
        ];
    }

    private function nullableConfigUrl(string $key): ?string
    {
        $url = config($key);

        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);

        return $url === '' ? null : $url;
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return list<array{
     *     id: int,
     *     orderNumber: string,
     *     title: string,
     *     status: string,
     *     statusTone: string,
     *     paymentType: string,
     *     paymentMethod: ?string,
     *     amountToman: int,
     *     createdAt: ?string
     * }>
     */
    private function mapOrderHistory(Collection $orders): array
    {
        return $orders
            ->map(function (Order $order): array {
                /** @var Payment|null $payment */
                $payment = $order->payments->first();

                return [
                    'id' => $order->id,
                    'orderNumber' => $order->order_number,
                    'title' => $order->coursePackage?->title ?? 'بسته دوره',
                    'status' => ProfileStatusLabels::orderStatus($order->status),
                    'statusTone' => ProfileStatusLabels::orderStatusTone($order->status),
                    'paymentType' => ProfileStatusLabels::paymentType($order->payment_type),
                    'paymentMethod' => $payment !== null
                        ? ProfileStatusLabels::paymentMethod($payment->method)
                        : null,
                    'amountToman' => $order->final_amount_toman,
                    'createdAt' => $order->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
