<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LockoutResponse;
use Laravel\Fortify\LoginRateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthLockoutResponse implements LockoutResponse
{
    public function __construct(
        private readonly LoginRateLimiter $limiter,
    ) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        throw new TooManyRequestsHttpException($this->limiter->availableIn($request));
    }
}
