<?php

namespace App\Services;

use App\Models\Payment;
use App\Services\Zarinpal\ZarinpalRequestResult;
use App\Services\Zarinpal\ZarinpalVerifyResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZarinpalService
{
    private const REQUEST_SUCCESS_CODE = 100;

    private const VERIFY_SUCCESS_CODE = 100;

    private const VERIFY_ALREADY_VERIFIED_CODE = 101;

    public function request(Payment $payment, string $callbackUrl, ?string $description = null): ZarinpalRequestResult
    {
        $merchantId = config('zarinpal.merchant_id');

        if (! is_string($merchantId) || $merchantId === '') {
            return ZarinpalRequestResult::failure('Zarinpal merchant id is not configured.');
        }

        $payment->loadMissing('order');

        $order = $payment->order;

        if ($order === null) {
            return ZarinpalRequestResult::failure('Payment order is missing.');
        }

        $payload = [
            'merchant_id' => $merchantId,
            'amount' => $this->amountInRials($payment->amount_toman),
            'callback_url' => $callbackUrl,
            'description' => $description ?? 'Order '.$order->order_number,
        ];

        if ($order->customer_mobile !== null && $order->customer_mobile !== '') {
            $payload['metadata'] = [
                'mobile' => $order->customer_mobile,
            ];
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->post($this->requestUrl(), $payload);
        } catch (\Throwable $exception) {
            Log::warning('Zarinpal payment request failed.', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return ZarinpalRequestResult::failure('Could not connect to Zarinpal.');
        }

        $body = $response->json();

        if (! is_array($body)) {
            return ZarinpalRequestResult::failure('Invalid Zarinpal response.');
        }

        $data = $body['data'] ?? null;
        $code = is_array($data) ? ($data['code'] ?? null) : null;
        $authority = is_array($data) ? ($data['authority'] ?? null) : null;

        if ($response->successful() && (int) $code === self::REQUEST_SUCCESS_CODE && is_string($authority) && $authority !== '') {
            return ZarinpalRequestResult::success(
                authority: $authority,
                paymentUrl: $this->paymentUrl($authority),
                rawResponse: $body,
            );
        }

        $errorMessage = $this->extractErrorMessage($body) ?? 'Zarinpal rejected the payment request.';

        Log::warning('Zarinpal payment request rejected.', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'code' => $code,
            'message' => $errorMessage,
        ]);

        return ZarinpalRequestResult::failure($errorMessage, $body);
    }

    public function verify(Payment $payment, string $authority): ZarinpalVerifyResult
    {
        $merchantId = config('zarinpal.merchant_id');

        if (! is_string($merchantId) || $merchantId === '') {
            return ZarinpalVerifyResult::failure('Zarinpal merchant id is not configured.');
        }

        $payload = [
            'merchant_id' => $merchantId,
            'amount' => $this->amountInRials($payment->amount_toman),
            'authority' => $authority,
        ];

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->post($this->verifyUrl(), $payload);
        } catch (\Throwable $exception) {
            Log::warning('Zarinpal payment verify failed.', [
                'payment_id' => $payment->id,
                'authority' => $authority,
                'message' => $exception->getMessage(),
            ]);

            return ZarinpalVerifyResult::failure('Could not connect to Zarinpal.');
        }

        $body = $response->json();

        if (! is_array($body)) {
            return ZarinpalVerifyResult::failure('Invalid Zarinpal response.');
        }

        $data = $body['data'] ?? null;
        $code = is_array($data) ? ($data['code'] ?? null) : null;
        $refId = is_array($data) ? ($data['ref_id'] ?? null) : null;

        if (
            $response->successful()
            && in_array((int) $code, [self::VERIFY_SUCCESS_CODE, self::VERIFY_ALREADY_VERIFIED_CODE], true)
        ) {
            $refIdString = match (true) {
                is_string($refId) && $refId !== '' => $refId,
                is_int($refId) => (string) $refId,
                default => null,
            };

            if ($refIdString === null) {
                return ZarinpalVerifyResult::failure('Zarinpal verify response is missing ref_id.', $body);
            }

            return ZarinpalVerifyResult::success($refIdString, $body);
        }

        $errorMessage = $this->extractErrorMessage($body) ?? 'Zarinpal rejected the payment verification.';

        Log::warning('Zarinpal payment verify rejected.', [
            'payment_id' => $payment->id,
            'authority' => $authority,
            'code' => $code,
            'message' => $errorMessage,
        ]);

        return ZarinpalVerifyResult::failure($errorMessage, $body);
    }

    public function paymentUrl(string $authority): string
    {
        return rtrim($this->baseUrl(), '/').'/StartPay/'.$authority;
    }

    public function amountInRials(int $amountToman): int
    {
        return $amountToman * 10;
    }

    private function requestUrl(): string
    {
        return rtrim($this->baseUrl(), '/').'/v4/payment/request.json';
    }

    private function verifyUrl(): string
    {
        return rtrim($this->baseUrl(), '/').'/v4/payment/verify.json';
    }

    private function baseUrl(): string
    {
        if (config('zarinpal.sandbox')) {
            return 'https://sandbox.zarinpal.com/pg';
        }

        return 'https://payment.zarinpal.com/pg';
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function extractErrorMessage(array $body): ?string
    {
        $errors = $body['errors'] ?? null;

        if (! is_array($errors) || $errors === []) {
            $data = $body['data'] ?? null;

            if (is_array($data) && isset($data['message']) && is_string($data['message']) && $data['message'] !== '') {
                return $data['message'];
            }

            return null;
        }

        $firstError = $errors[0] ?? null;

        if (is_array($firstError) && isset($firstError['message']) && is_string($firstError['message'])) {
            return $firstError['message'];
        }

        if (is_string($firstError) && $firstError !== '') {
            return $firstError;
        }

        return null;
    }
}
