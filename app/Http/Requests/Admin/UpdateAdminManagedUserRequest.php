<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ProfileValidationRules;
use App\Concerns\UsernameValidationRules;
use App\Models\User;
use App\Support\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAdminManagedUserRequest extends FormRequest
{
    use ProfileValidationRules;
    use UsernameValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        /** @var User $user */
        $user = $this->route('user');

        $merged = [];

        if ($this->has('username') && is_string($this->input('username'))) {
            $merged['username'] = strtolower(trim($this->input('username')));
        }

        if ($this->has('mobile') && is_string($this->input('mobile'))) {
            $trimmedMobile = trim($this->input('mobile'));

            if ($trimmedMobile === '') {
                $merged['mobile'] = null;
            } else {
                $merged['mobile'] = IranianMobile::normalize($trimmedMobile) ?? $trimmedMobile;
            }
        }

        $submittedMobile = $merged['mobile'] ?? $this->input('mobile');
        $normalizedSubmitted = is_string($submittedMobile)
            ? IranianMobile::normalize($submittedMobile)
            : null;

        $mobileChanging = $normalizedSubmitted !== null && $normalizedSubmitted !== $user->mobile;
        $mobileAdding = ! filled($user->mobile) && filled($normalizedSubmitted);

        if (($mobileChanging || $mobileAdding) && ! $this->has('verify_mobile')) {
            $merged['verify_mobile'] = true;
        }

        if ($this->has('verify_mobile')) {
            $merged['verify_mobile'] = filter_var(
                $this->input('verify_mobile'),
                FILTER_VALIDATE_BOOLEAN,
            );
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => $this->nameRules(),
            'username' => $this->usernameRules($user->id, required: false),
            'mobile' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    if (! IranianMobile::isValid($value)) {
                        $fail(IranianMobile::validationMessage($value));
                    }
                },
                Rule::unique(User::class, 'mobile')->ignore($user->id),
            ],
            'verify_mobile' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((bool) $this->input('verify_mobile') !== true) {
                return;
            }

            $mobile = $this->input('mobile');

            if (! is_string($mobile) || trim($mobile) === '') {
                $validator->errors()->add(
                    'mobile',
                    'برای تأیید موبایل، شماره موبایل لازم است.',
                );

                return;
            }

            if (! IranianMobile::isValid($mobile)) {
                $validator->errors()->add(
                    'mobile',
                    IranianMobile::validationMessage($mobile),
                );
            }
        });
    }
}
