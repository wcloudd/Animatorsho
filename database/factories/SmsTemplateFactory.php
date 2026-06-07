<?php

namespace Database\Factories;

use App\Enums\SmsMessageType;
use App\Models\SmsTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsTemplate>
 */
class SmsTemplateFactory extends Factory
{
    protected $model = SmsTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(SmsMessageType::seededTypes());
        $config = config('sms.templates.'.$type->value, []);

        return [
            'key' => $type->value,
            'title' => is_string($config['title'] ?? null) ? $config['title'] : 'قالب پیامک',
            'body' => is_string($config['body'] ?? null) ? $config['body'] : 'پیام تست',
            'is_enabled' => true,
            'description' => is_string($config['description'] ?? null) ? $config['description'] : null,
        ];
    }
}
