<?php

namespace App\Services\Admin;

use App\DataTransferObjects\Admin\ManualEnrollmentResult;
use App\Enums\ExternalEnrollmentSource;
use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use App\Services\SpotPlayer\SpotPlayerApiProvisioningService;
use App\Services\SpotPlayerLicenseProvisioningService;
use App\Services\UserPackagePurchaseGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminManualEnrollmentService
{
    public function __construct(
        private readonly AdminUserLookupService $userLookup,
        private readonly UserPackagePurchaseGuard $purchaseGuard,
        private readonly SpotPlayerLicenseProvisioningService $licenseProvisioning,
        private readonly AdminSpotPlayerLicenseService $licenseActivation,
        private readonly SpotPlayerApiProvisioningService $spotPlayerApi,
    ) {}

    public function grant(
        User $admin,
        string $customerName,
        ?string $userLookup,
        ?string $customerMobile,
        CoursePackage $package,
        ExternalEnrollmentSource $source,
        ?string $note = null,
        ?string $licenseKey = null,
    ): ManualEnrollmentResult {
        $trimmedNote = is_string($note) ? trim($note) : '';
        $trimmedLicenseKey = is_string($licenseKey) ? trim($licenseKey) : '';
        $licenseKeyProvided = $trimmedLicenseKey !== '';

        /** @var array{user: User, created: bool, order: Order, license: SpotPlayerLicense} $created */
        $created = DB::transaction(function () use (
            $admin,
            $customerName,
            $userLookup,
            $customerMobile,
            $package,
            $source,
            $trimmedNote,
        ): array {
            $lookup = $this->userLookup->resolve($customerName, $userLookup, $customerMobile);
            $user = $lookup->user;

            if ($this->purchaseGuard->hasBlockingAccess($user, $package)) {
                throw ValidationException::withMessages([
                    'course_package_id' => $this->purchaseGuard->message(),
                ]);
            }

            $order = new Order([
                'user_id' => $user->id,
                'course_package_id' => $package->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => OrderStatus::Paid,
                'payment_type' => OrderPaymentType::External,
                'customer_name' => trim($customerName),
                'customer_mobile' => $lookup->orderCustomerMobile,
            ]);
            $order->snapshotAmountsFromPackage($package);
            $order->save();

            Payment::query()->create([
                'order_id' => $order->id,
                'method' => PaymentMethod::External,
                'status' => PaymentStatus::Paid,
                'amount_toman' => $order->final_amount_toman,
                'paid_at' => now(),
                'meta' => [
                    'external_source' => $source->value,
                    'admin_note' => $trimmedNote !== '' ? $trimmedNote : null,
                    'granted_by_user_id' => $admin->id,
                    'granted_at' => now()->toIso8601String(),
                ],
            ]);

            $license = $this->licenseProvisioning->provisionForPaidOrder($order->fresh());

            if (! $license instanceof SpotPlayerLicense) {
                throw new \RuntimeException('SpotPlayer license could not be provisioned.');
            }

            return [
                'user' => $user,
                'created' => $lookup->created,
                'order' => $order->fresh(['coursePackage', 'payments', 'spotPlayerLicense']),
                'license' => $license->fresh(['coursePackage', 'order']),
            ];
        });

        $license = $created['license'];

        if ($licenseKeyProvided) {
            $license = $this->licenseActivation->activate($license, $trimmedLicenseKey);
        } else {
            $license = $this->spotPlayerApi->attemptForLicense($license);
        }

        return new ManualEnrollmentResult(
            user: $created['user']->fresh(),
            order: $created['order']->fresh(['coursePackage', 'payments', 'spotPlayerLicense']),
            license: $license->fresh(['coursePackage', 'order']),
            userCreated: $created['created'],
            licenseActive: $license->status === SpotPlayerLicenseStatus::Active,
        );
    }
}
