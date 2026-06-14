<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Services\Course\CourseAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ProfileDeleteRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // OTP-only accounts have no password, so they must not be forced to
        // pass current_password (they would otherwise be unable to delete).
        return [
            'password' => $this->currentPasswordRulesForUser($this->user()),
        ];
    }

    /**
     * Block self-service deletion while the account still has active course
     * access, so a student does not accidentally lose their license linkage.
     * Recovering access / revoking the SpotPlayer license stays a manual
     * support task. The error is attached to the existing "password" field so
     * it surfaces in the current delete dialog without any UI change.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();

            if ($user === null) {
                return;
            }

            if (app(CourseAccessService::class)->userHasActiveAccess($user)) {
                $validator->errors()->add(
                    'password',
                    'حساب شما دسترسی فعال به دوره دارد و برای حفظ لایسنس قابل حذف نیست. لطفاً برای حذف حساب با پشتیبانی در تماس باشید.',
                );
            }
        });
    }
}
