<?php

namespace App\Services\Security;

use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class SecurityEventLogger
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN_CONTEXT_KEYS = [
        'password',
        'password_confirmation',
        'code',
        'otp',
        'token',
        'reset_token',
        'authority',
        'ref_id',
        'preferred_contact_window',
        'email',
        'mobile',
        'session_id',
        'authorization',
        'body',
        'request_body',
    ];

    /**
     * @var array<string, string>
     */
    private const ROUTE_LIMITER_MAP = [
        'login.store' => 'login',
        'login.email.store' => 'login',
        'login.identifier' => 'auth-identifier',
        'register.store' => 'registration-otp-send',
        'register.verify.store' => 'registration-otp-verify',
        'register.resend-code' => 'registration-otp-send',
        'register.change-mobile' => 'registration-otp-send',
        'password.email' => 'password-reset-email-send',
        'password.update' => 'password-reset-email-submit',
        'auth.mobile.send-code' => 'mobile-otp-send',
        'auth.mobile.resend-code' => 'mobile-otp-send',
        'auth.mobile.verify.store' => 'mobile-otp-verify',
        'password.mobile.send-code' => 'password-reset-otp-send',
        'password.mobile.resend-code' => 'password-reset-otp-send',
        'password.mobile.verify.store' => 'password-reset-otp-verify',
        'password.mobile.reset.store' => 'password-reset-mobile-submit',
    ];

    public function log(string $event, array $context = [], ?Request $request = null): void
    {
        if (! config('security.logging.enabled', true)) {
            return;
        }

        $request ??= request();
        $payload = array_merge($this->baseContext($event, $request), $context);
        $payload = $this->stripForbiddenKeys($payload);

        Log::channel((string) config('security.logging.channel', 'security'))
            ->warning($event, $payload);

        $this->persistToDatabase($event, $payload);
    }

    public function honeypotTriggered(?Request $request = null): void
    {
        $this->log('honeypot_triggered', [], $request);
    }

    public function authRateLimitExceeded(TooManyRequestsHttpException $exception, ?Request $request = null): void
    {
        $request ??= request();

        $context = [
            'limiter' => $this->inferLimiterFromRoute($request),
        ];

        $retryAfter = $exception->getHeaders()['Retry-After'] ?? null;

        if ($retryAfter !== null && is_numeric($retryAfter)) {
            $context['retry_after_seconds'] = (int) $retryAfter;
        }

        $this->log('auth_rate_limit_exceeded', $context, $request);
    }

    public function paymentRetryCeilingReached(
        int $orderId,
        int $paymentId,
        int $retryCount,
        int $maxRetries,
        ?Request $request = null,
    ): void {
        $this->log('payment_retry_ceiling_reached', [
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'retry_count' => $retryCount,
            'max_retries' => $maxRetries,
        ], $request);
    }

    public function consultationDuplicateBlocked(?int $openConsultationRequestId = null, ?Request $request = null): void
    {
        $context = [];

        if ($openConsultationRequestId !== null) {
            $context['open_consultation_request_id'] = $openConsultationRequestId;
        }

        $this->log('consultation_duplicate_blocked', $context, $request);
    }

    public function supportOpenTicketCapReached(int $openTicketCount, int $maxOpenTickets, ?Request $request = null): void
    {
        $this->log('support_open_ticket_cap_reached', [
            'open_ticket_count' => $openTicketCount,
            'max_open_tickets' => $maxOpenTickets,
        ], $request);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseContext(string $event, Request $request): array
    {
        return [
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $this->truncateUserAgent($request->userAgent()),
        ];
    }

    private function truncateUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        $maxLength = (int) config('security.logging.user_agent_max_length', 200);

        if (strlen($userAgent) <= $maxLength) {
            return $userAgent;
        }

        return substr($userAgent, 0, $maxLength);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function stripForbiddenKeys(array $payload): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), self::FORBIDDEN_CONTEXT_KEYS, true)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->stripForbiddenKeys($value);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private function inferLimiterFromRoute(Request $request): ?string
    {
        $routeName = $request->route()?->getName();

        if ($routeName === null) {
            return null;
        }

        return self::ROUTE_LIMITER_MAP[$routeName] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistToDatabase(string $event, array $payload): void
    {
        if (! config('security.logging.database_enabled', true)) {
            return;
        }

        try {
            $occurredAt = isset($payload['occurred_at'])
                ? now()->parse((string) $payload['occurred_at'])
                : now();

            $meta = $payload;
            unset(
                $meta['event'],
                $meta['occurred_at'],
                $meta['route'],
                $meta['method'],
                $meta['user_id'],
                $meta['ip'],
                $meta['user_agent'],
            );

            SecurityEvent::query()->create([
                'event' => $event,
                'occurred_at' => $occurredAt,
                'user_id' => $payload['user_id'] ?? null,
                'route' => $payload['route'] ?? null,
                'method' => $payload['method'] ?? null,
                'ip' => $payload['ip'] ?? null,
                'user_agent' => $payload['user_agent'] ?? null,
                'meta' => $meta === [] ? null : $meta,
            ]);
        } catch (Throwable) {
            // Database persistence must never interrupt request flows.
        }
    }
}
