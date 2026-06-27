<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectCardToCardPaymentRequest;
use App\Models\Payment;
use App\Services\Admin\AdminPaymentListService;
use App\Services\Admin\AdminPaymentReviewService;
use App\Services\PaymentReceiptStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly AdminPaymentListService $payments,
        private readonly AdminPaymentReviewService $paymentReview,
        private readonly PaymentReceiptStorageService $receipts,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $search = $request->string('q')->toString();
        $focus = $request->has('focus') ? $request->integer('focus') : null;

        return Inertia::render('admin/payments/index', $this->payments->listForAdmin(
            $status !== '' ? $status : null,
            $search !== '' ? $search : null,
            $focus,
        ));
    }

    public function approve(Payment $payment): RedirectResponse
    {
        try {
            $this->paymentReview->approve($payment);
        } catch (InvalidArgumentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return redirect()->back();
        }

        $message = $payment->method === PaymentMethod::Installment
            ? 'درخواست اقساطی تأیید شد و سفارش پرداخت‌شده ثبت شد.'
            : 'رسید تأیید شد و سفارش پرداخت‌شده ثبت شد.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return redirect()->back();
    }

    public function reject(RejectCardToCardPaymentRequest $request, Payment $payment): RedirectResponse
    {
        try {
            $this->paymentReview->reject(
                $payment,
                $request->validated('note'),
            );
        } catch (InvalidArgumentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return redirect()->back();
        }

        $message = $payment->method === PaymentMethod::Installment
            ? 'درخواست اقساطی رد شد.'
            : 'رسید رد شد.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return redirect()->back();
    }

    public function receipt(Payment $payment): StreamedResponse
    {
        // Receipts exist for full card-to-card payments and for card-to-card
        // installment down payments (stored on the Installment payment).
        if (! in_array($payment->method, [PaymentMethod::CardToCard, PaymentMethod::Installment], true)) {
            abort(404);
        }

        if (! $this->receipts->hasReceipt($payment)) {
            abort(404);
        }

        return $this->receipts->streamResponse($payment);
    }
}
