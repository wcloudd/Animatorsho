<?php

namespace App\Services\Sms;

use App\Enums\SmsMessageType;
use App\Models\SmsTemplate;

class SmsTemplateService
{
    public function isEnabled(SmsMessageType $type): bool
    {
        return $this->resolveTemplate($type)->isEnabled;
    }

    /**
     * @param  array<string, string|null>  $context
     */
    public function render(SmsMessageType $type, array $context = []): string
    {
        $template = $this->resolveTemplate($type);
        $body = $template->body;

        return preg_replace_callback(
            '/\{([a-z_]+)\}/',
            function (array $matches) use ($context): string {
                $key = $matches[1];
                $value = $context[$key] ?? '';

                return is_string($value) ? $value : '';
            },
            $body,
        ) ?? $body;
    }

    /**
     * @return list<array{
     *     id: int,
     *     key: string,
     *     title: string,
     *     body: string,
     *     isEnabled: bool,
     *     description: string|null
     * }>
     */
    public function allForAdmin(): array
    {
        $this->ensureSeeded();

        return SmsTemplate::query()
            ->whereIn('key', array_map(fn (SmsMessageType $type): string => $type->value, SmsMessageType::seededTypes()))
            ->orderBy('id')
            ->get()
            ->map(fn (SmsTemplate $template): array => [
                'id' => $template->id,
                'key' => $template->key,
                'title' => $template->title,
                'body' => $template->body,
                'isEnabled' => $template->is_enabled,
                'description' => $template->description,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{title?: string, body?: string, is_enabled?: bool}  $data
     */
    public function update(SmsTemplate $template, array $data): SmsTemplate
    {
        $updates = [];

        if (array_key_exists('title', $data) && is_string($data['title'])) {
            $updates['title'] = $data['title'];
        }

        if (array_key_exists('body', $data) && is_string($data['body'])) {
            $updates['body'] = $data['body'];
        }

        if (array_key_exists('is_enabled', $data)) {
            $updates['is_enabled'] = (bool) $data['is_enabled'];
        }

        if ($updates !== []) {
            $template->update($updates);
        }

        return $template->fresh();
    }

    public function ensureSeeded(): void
    {
        foreach (SmsMessageType::seededTypes() as $type) {
            if (SmsTemplate::query()->where('key', $type->value)->exists()) {
                continue;
            }

            $defaults = config('sms.templates.'.$type->value, []);

            SmsTemplate::query()->create([
                'key' => $type->value,
                'title' => is_string($defaults['title'] ?? null) ? $defaults['title'] : $type->value,
                'body' => is_string($defaults['body'] ?? null) ? $defaults['body'] : '',
                'description' => is_string($defaults['description'] ?? null) ? $defaults['description'] : null,
                'is_enabled' => true,
            ]);
        }
    }

    /**
     * @return object{body: string, isEnabled: bool, title: string}
     */
    private function resolveTemplate(SmsMessageType $type): object
    {
        $template = SmsTemplate::query()->where('key', $type->value)->first();

        if ($template instanceof SmsTemplate) {
            return (object) [
                'body' => $template->body,
                'isEnabled' => $template->is_enabled,
                'title' => $template->title,
            ];
        }

        $defaults = config('sms.templates.'.$type->value, []);

        return (object) [
            'body' => is_string($defaults['body'] ?? null) ? $defaults['body'] : '',
            'isEnabled' => true,
            'title' => is_string($defaults['title'] ?? null) ? $defaults['title'] : $type->value,
        ];
    }
}
