<?php

namespace App\Services\Sms;

use App\Enums\SmsMessageStatus;
use App\Enums\SmsMessageType;
use App\Models\SmsMessage;
use App\Services\Sms\Contracts\SmsDriver;
use App\Services\Sms\Drivers\FakeSmsDriver;
use App\Services\Sms\Drivers\FarazSmsDriver;
use App\Services\Sms\Drivers\LogSmsDriver;
use App\Support\IranianMobile;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function __construct(
        private readonly SmsSettingsService $settings,
        private readonly SmsTemplateService $templates,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function send(string $mobile, string $message, ?SmsMessageType $type = null, array $meta = []): void
    {
        try {
            $this->dispatch($mobile, $message, $type, $meta, forAdmin: false);
        } catch (\Throwable $exception) {
            Log::warning('SMS send failed unexpectedly.', [
                'mobile' => $mobile,
                'type' => $type?->value,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function sendToAdmin(string $message, ?SmsMessageType $type = null, array $meta = []): void
    {
        try {
            $this->dispatch(
                $this->settings->adminMobile() ?? '',
                $message,
                $type,
                $meta,
                forAdmin: true,
            );
        } catch (\Throwable $exception) {
            Log::warning('Admin SMS send failed unexpectedly.', [
                'type' => $type?->value,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function dispatch(
        string $mobile,
        string $message,
        ?SmsMessageType $type,
        array $meta,
        bool $forAdmin,
    ): void {
        $provider = $this->settings->currentDriver();
        $normalizedMobile = IranianMobile::normalize($mobile);

        if (! $this->settings->isEnabled()) {
            $this->recordSkipped($normalizedMobile, $message, $type, $provider, $meta, 'global_disabled');

            return;
        }

        if ($type instanceof SmsMessageType && ! $this->templates->isEnabled($type)) {
            $this->recordSkipped($normalizedMobile, $message, $type, $provider, $meta, 'template_disabled');

            return;
        }

        if ($forAdmin && ! $this->settings->isAdminNotificationsEnabled()) {
            $this->recordSkipped($normalizedMobile, $message, $type, $provider, $meta, 'admin_disabled');

            return;
        }

        if ($normalizedMobile === null) {
            $skipReason = $forAdmin ? 'missing_admin_mobile' : 'invalid_mobile';
            $this->recordSkipped(null, $message, $type, $provider, $meta, $skipReason);

            return;
        }

        $record = SmsMessage::query()->create([
            'mobile' => $normalizedMobile,
            'message' => $message,
            'type' => $type?->value,
            'status' => SmsMessageStatus::Pending,
            'provider' => $provider,
            'meta' => $meta === [] ? null : $meta,
        ]);

        try {
            $result = $this->driver()->send($record);
            $mergedMeta = array_merge($record->meta ?? [], $result->meta);

            $record->update([
                'status' => $result->success ? SmsMessageStatus::Sent : SmsMessageStatus::Failed,
                'sent_at' => $result->success ? now() : null,
                'meta' => $mergedMeta === [] ? null : $mergedMeta,
            ]);
        } catch (\Throwable $exception) {
            $record->update([
                'status' => SmsMessageStatus::Failed,
                'meta' => array_merge($record->meta ?? [], [
                    'provider_error' => 'driver_exception',
                ]),
            ]);

            Log::warning('SMS driver failed.', [
                'sms_message_id' => $record->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function recordSkipped(
        ?string $mobile,
        string $message,
        ?SmsMessageType $type,
        string $provider,
        array $meta,
        string $skipReason,
    ): void {
        SmsMessage::query()->create([
            'mobile' => $mobile,
            'message' => $message,
            'type' => $type?->value,
            'status' => SmsMessageStatus::Skipped,
            'provider' => $provider,
            'meta' => array_merge($meta, ['skip_reason' => $skipReason]),
            'sent_at' => null,
        ]);
    }

    private function driver(): SmsDriver
    {
        return match ($this->settings->currentDriver()) {
            'fake' => app(FakeSmsDriver::class),
            'farazsms' => app(FarazSmsDriver::class),
            'log' => app(LogSmsDriver::class),
            default => app(LogSmsDriver::class),
        };
    }
}
