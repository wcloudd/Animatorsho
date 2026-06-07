<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Support\IranianMobile;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        if (isset($input['mobile']) && is_string($input['mobile'])) {
            $normalized = IranianMobile::normalize($input['mobile']);

            if ($normalized !== null) {
                $input['mobile'] = $normalized;
            }
        }

        Validator::make($input, [
            ...$this->profileRules(),
            ...$this->registrationMobileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'mobile' => $this->normalizedMobileFromInput($input),
        ]);
    }
}
