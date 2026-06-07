<?php

namespace App\Services\Admin;

use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Services\Sms\SmsSettingsService;
use App\Services\Sms\SmsTemplateService;
use App\Support\Admin\AdminListSearch;
use App\Support\AdminStatusLabels;
use App\Support\SmsStatusLabels;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminSmsService
{
    public function __construct(
        private readonly SmsSettingsService $settings,
        private readonly SmsTemplateService $templates,
    ) {}

    /**
     * @return array{
     *     settings: array{
     *         enabled: bool,
     *         adminNotificationsEnabled: bool,
     *         adminMobile: string|null,
     *         driver: string,
     *         driverLabel: string,
     *         driverConfigured: bool
     *     },
     *     templates: list<array{
     *         id: int,
     *         key: string,
     *         title: string,
     *         body: string,
     *         isEnabled: bool,
     *         description: string|null
     *     }>
     * }
     */
    public function indexForAdmin(): array
    {
        $this->templates->ensureSeeded();

        return [
            'settings' => $this->settings->toAdminArray(),
            'templates' => $this->templates->allForAdmin(),
        ];
    }

    /**
     * @return array{
     *     logs: LengthAwarePaginator<int, array<string, mixed>>,
     *     filters: array{q: ?string}
     * }
     */
    public function logsForAdmin(?string $search = null): array
    {
        $query = SmsMessage::query()->latest();

        AdminListSearch::apply($query, $search, function (Builder $searchQuery, string $pattern): void {
            $searchQuery
                ->where('mobile', 'like', $pattern)
                ->orWhere('message', 'like', $pattern)
                ->orWhere('type', 'like', $pattern)
                ->orWhere('provider', 'like', $pattern);
        });

        $normalizedSearch = AdminListSearch::normalize($search);

        $logs = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SmsMessage $message): array => $this->toLogItem($message));

        return [
            'logs' => $logs,
            'filters' => [
                'q' => $normalizedSearch,
            ],
        ];
    }

    /**
     * @param  array{enabled: bool, admin_notifications_enabled: bool, admin_mobile?: string|null}  $data
     */
    public function updateSettings(array $data): void
    {
        $this->settings->update([
            'enabled' => $data['enabled'],
            'admin_notifications_enabled' => $data['admin_notifications_enabled'],
            'admin_mobile' => $data['admin_mobile'] ?? null,
        ]);
    }

    /**
     * @param  array{title: string, body: string, is_enabled: bool}  $data
     */
    public function updateTemplate(SmsTemplate $template, array $data): void
    {
        $this->templates->update($template, $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toLogItem(SmsMessage $message): array
    {
        $type = $message->messageType();
        $status = $message->status;

        return [
            'id' => $message->id,
            'mobile' => $message->mobile,
            'type' => $type !== null ? SmsStatusLabels::type($type) : ($message->type ?? '—'),
            'typeValue' => $message->type,
            'status' => $status !== null ? SmsStatusLabels::status($status) : '—',
            'statusValue' => $status?->value,
            'statusTone' => $status !== null ? AdminStatusLabels::smsStatusTone($status) : 'neutral',
            'provider' => $message->provider,
            'messagePreview' => mb_strlen($message->message) > 120
                ? mb_substr($message->message, 0, 120).'…'
                : $message->message,
            'message' => $message->message,
            'meta' => $message->meta !== null ? json_encode($message->meta, JSON_UNESCAPED_UNICODE) : null,
            'sentAt' => $message->sent_at?->toIso8601String(),
            'createdAt' => $message->created_at?->toIso8601String(),
        ];
    }
}
