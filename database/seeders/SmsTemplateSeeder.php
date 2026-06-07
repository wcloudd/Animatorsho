<?php

namespace Database\Seeders;

use App\Enums\SmsMessageType;
use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SmsMessageType::seededTypes() as $type) {
            $defaults = config('sms.templates.'.$type->value, []);

            SmsTemplate::query()->firstOrCreate(
                ['key' => $type->value],
                [
                    'title' => is_string($defaults['title'] ?? null) ? $defaults['title'] : $type->value,
                    'body' => is_string($defaults['body'] ?? null) ? $defaults['body'] : '',
                    'description' => is_string($defaults['description'] ?? null) ? $defaults['description'] : null,
                    'is_enabled' => true,
                ],
            );
        }
    }
}
