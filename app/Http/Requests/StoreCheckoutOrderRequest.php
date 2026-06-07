<?php

namespace App\Http\Requests;

use App\Concerns\CustomerValidationRules;
use App\Services\CheckoutOrderService;
use App\Services\PaymentReceiptStorageService;
use App\Services\UserPackagePurchaseGuard;
use App\Support\IranianMobile;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutOrderRequest extends FormRequest
{
    use CustomerValidationRules;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $merged = $this->all();

        if ($this->usesAccountMobileSnapshot($user)) {
            $merged['customer_mobile'] = IranianMobile::normalize($user->mobile) ?? $user->mobile;
        } else {
            $merged = $this->normalizedCustomerInput($merged);
        }

        if (isset($merged['customer_name']) && is_string($merged['customer_name'])) {
            $merged['customer_name'] = trim($merged['customer_name']);
        }

        if (! array_key_exists('payment_channel', $merged)) {
            $merged['payment_channel'] = 'online';
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isInstallment = $this->input('payment') === 'installment'
            && $this->input('package') === 'full';

        $isCardToCard = $this->input('payment') === 'cash'
            && $this->input('payment_channel') === 'card_to_card';

        $receiptMaxKb = (int) config('card_to_card.receipt_max_kb', 5120);

        return [
            'package' => ['required', 'string', Rule::in(['full', 'chapter'])],
            'payment' => ['required', 'string', Rule::in(['cash', 'installment'])],
            'payment_channel' => ['nullable', 'string', Rule::in(['online', 'card_to_card'])],
            'chapter' => ['nullable', 'string', 'max:255'],
            ...$this->checkoutCustomerInfoRules($this->user()),
            'installment_term' => [
                Rule::requiredIf($isInstallment),
                'nullable',
                'string',
                Rule::in(['one_month', 'two_months']),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
            'receipt_image' => [
                Rule::requiredIf($isCardToCard),
                'nullable',
                'file',
                'mimes:'.implode(',', config('card_to_card.receipt_mimes', ['jpg', 'jpeg', 'png', 'webp'])),
                'max:'.$receiptMaxKb,
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $package = $this->string('package')->toString();
            $payment = $this->string('payment')->toString();
            $paymentChannel = $this->string('payment_channel')->toString();
            $chapter = $this->input('chapter');

            if ($payment !== 'cash' && $paymentChannel === 'card_to_card') {
                $validator->errors()->add(
                    'payment_channel',
                    'Card-to-card is only available for cash checkout.',
                );

                return;
            }

            if ($paymentChannel === 'card_to_card' && ! app(PaymentReceiptStorageService::class)->isConfigured()) {
                $validator->errors()->add(
                    'payment_channel',
                    'Card-to-card payment is not configured yet.',
                );

                return;
            }

            if ($package === 'chapter' && $payment === 'installment') {
                $validator->errors()->add(
                    'payment',
                    'Installment purchase is only available for the full course.',
                );

                return;
            }

            if ($package === 'chapter' && (! is_string($chapter) || $chapter === '')) {
                $validator->errors()->add(
                    'chapter',
                    'A chapter must be selected.',
                );

                return;
            }

            try {
                $coursePackage = app(CheckoutOrderService::class)->resolvePackage(
                    $package,
                    $payment,
                    is_string($chapter) ? $chapter : null,
                );
            } catch (\InvalidArgumentException $exception) {
                $validator->errors()->add('package', $exception->getMessage());

                return;
            }

            $user = $this->user();

            if (
                $user !== null
                && app(UserPackagePurchaseGuard::class)->hasBlockingAccess($user, $coursePackage)
            ) {
                $validator->errors()->add(
                    'package',
                    app(UserPackagePurchaseGuard::class)->message(),
                );
            }
        });
    }
}
