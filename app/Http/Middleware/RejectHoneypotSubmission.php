<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RejectHoneypotSubmission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.honeypot.enabled', true)) {
            return $next($request);
        }

        $fieldName = (string) config('security.honeypot.field_name');

        if ($fieldName === '' || ! $request->filled($fieldName)) {
            return $next($request);
        }

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => (string) config('security.honeypot.message'),
            ]);
        }

        return redirect()
            ->back()
            ->withInput($request->except($fieldName, 'password', 'password_confirmation'));
    }
}
