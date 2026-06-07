<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OnlinePaymentRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class ProfileOrderController extends Controller
{
    public function __construct(
        private readonly OnlinePaymentRecoveryService $onlinePaymentRecovery,
    ) {}

    public function retryOnlinePayment(Request $request, Order $order): RedirectResponse|Response
    {
        try {
            return $this->onlinePaymentRecovery->retryOnlinePayment($order, $request->user());
        } catch (InvalidArgumentException) {
            abort(403);
        }
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        try {
            $this->onlinePaymentRecovery->cancelPendingOnlineOrder($order, $request->user());
        } catch (InvalidArgumentException) {
            abort(403);
        }

        return redirect()
            ->route('profile')
            ->with('success', 'سفارش لغو شد. در صورت نیاز می‌توانید دوباره ثبت‌نام کنید.');
    }
}
