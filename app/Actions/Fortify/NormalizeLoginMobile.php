<?php

namespace App\Actions\Fortify;

use App\Support\IranianMobile;
use Illuminate\Http\Request;

class NormalizeLoginMobile
{
    /**
     * Handle the incoming request.
     *
     * @param  callable(Request): mixed  $next
     */
    public function handle(Request $request, callable $next): mixed
    {
        if ($request->has('mobile')) {
            $normalized = IranianMobile::normalize($request->input('mobile'));

            if ($normalized !== null) {
                $request->merge(['mobile' => $normalized]);
            }
        }

        return $next($request);
    }
}
