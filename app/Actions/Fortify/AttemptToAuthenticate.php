<?php

namespace App\Actions\Fortify;

use App\Support\LoginIdentifier;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\AttemptToAuthenticate as FortifyAttemptToAuthenticate;

class AttemptToAuthenticate extends FortifyAttemptToAuthenticate
{
    /**
     * Throw a failed authentication validation exception.
     *
     * @param  Request  $request
     *
     * @throws ValidationException
     */
    protected function throwFailedAuthenticationException($request)
    {
        $this->limiter->increment($request);

        throw ValidationException::withMessages([
            LoginIdentifier::credentialField($request) => [trans('auth.failed')],
        ]);
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     *
     * @param  Request  $request
     */
    protected function fireFailedEvent($request)
    {
        $credentialField = LoginIdentifier::credentialField($request);

        event(new Failed($this->guard?->name ?? config('fortify.guard'), null, [
            $credentialField => $request->input($credentialField),
            'password' => $request->password,
        ]));
    }
}
