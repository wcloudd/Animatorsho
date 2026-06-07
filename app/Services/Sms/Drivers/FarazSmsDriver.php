<?php

namespace App\Services\Sms\Drivers;

use App\Models\SmsMessage;
use App\Services\Sms\Contracts\SmsDriver;
use App\Services\Sms\SmsSendResult;
use App\Support\IranianMobile;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FarazSmsDriver implements SmsDriver
{
    public function send(SmsMessage $message): SmsSendResult
    {
        $config = config('sms.providers.farazsms');

        $apiKey = is_array($config) ? ($config['api_key'] ?? null) : null;
        $sender = is_array($config) ? ($config['sender'] ?? null) : null;
        $baseUrl = is_array($config) ? ($config['base_url'] ?? null) : null;

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($sender) || $sender === '') {
            return SmsSendResult::failure([
                'provider_error' => 'configuration_missing',
            ]);
        }

        $recipient = IranianMobile::normalize($message->mobile);

        if ($recipient === null) {
            return SmsSendResult::failure([
                'provider_error' => 'invalid_mobile',
            ]);
        }

        $url = rtrim(is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : 'https://api.iranpayamak.com/ws/v1', '/').'/sms/simple';

        $payload = [
            'text' => $message->message,
            'line_number' => $sender,
            'recipients' => [$recipient],
            'number_format' => 'english',
            'schedule' => null,
        ];

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);
        } catch (\Throwable $exception) {
            Log::warning('FarazSMS send failed.', [
                'sms_message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);

            return SmsSendResult::failure([
                'provider_error' => 'connection_failed',
            ]);
        }

        $body = $response->json();

        if (! is_array($body)) {
            $failureMeta = [
                'provider_error' => 'invalid_response',
                'http_status' => $response->status(),
                'response_preview' => $this->sanitizeResponsePreview($response->body(), $apiKey),
            ];

            Log::warning('FarazSMS returned invalid response.', [
                'sms_message_id' => $message->id,
                'http_status' => $failureMeta['http_status'],
            ]);

            return SmsSendResult::failure($failureMeta);
        }

        if ($this->isSendSuccessful($response, $body)) {
            return SmsSendResult::success($this->buildSuccessMeta($body, $response));
        }

        $failureMeta = $this->buildSendRejectedMeta($body, $response, $apiKey);

        Log::warning('FarazSMS rejected message.', [
            'sms_message_id' => $message->id,
            'http_status' => $failureMeta['http_status'],
            'provider_message_code' => $failureMeta['provider_message_code'] ?? null,
            'provider_message' => $failureMeta['provider_message'] ?? null,
        ]);

        return SmsSendResult::failure($failureMeta);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function isSendSuccessful(Response $response, array $body): bool
    {
        if (! in_array($response->status(), [200, 201], true)) {
            return false;
        }

        return ($body['status'] ?? null) === 'success';
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function buildSuccessMeta(array $body, Response $response): array
    {
        $successMeta = [
            'provider_http_status' => $response->status(),
        ];

        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        $messageId = $this->firstNonEmptyString([
            $data['message_id'] ?? null,
            $data['id'] ?? null,
            $body['message_id'] ?? null,
            is_int($body['data'] ?? null) || is_string($body['data'] ?? null) ? $body['data'] : null,
        ]);

        if ($messageId !== null) {
            $successMeta['provider_message_id'] = is_numeric($messageId) ? (int) $messageId : $messageId;
        }

        $providerMessage = $this->firstNonEmptyString([
            $body['message'] ?? null,
            $data['message'] ?? null,
        ]);

        if ($providerMessage !== null) {
            $successMeta['provider_message'] = $providerMessage;
        }

        $messageCode = $this->firstNonEmptyString([
            $body['code'] ?? null,
            $data['code'] ?? null,
        ]);

        if ($messageCode !== null) {
            $successMeta['provider_message_code'] = $messageCode;
        }

        return $successMeta;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function buildSendRejectedMeta(array $body, Response $response, ?string $apiKey): array
    {
        $fields = $this->extractProviderFields($body);

        $failureMeta = [
            'provider_error' => 'send_rejected',
            'http_status' => $response->status(),
        ];

        if ($fields['provider_message_code'] !== null) {
            $failureMeta['provider_message_code'] = $fields['provider_message_code'];
        }

        if ($fields['provider_message'] !== null) {
            $failureMeta['provider_message'] = $fields['provider_message'];
        }

        $responseKeys = array_values(array_filter(array_keys($body), 'is_string'));

        if ($responseKeys !== []) {
            $failureMeta['response_keys'] = $responseKeys;
        }

        $encoded = json_encode($body, JSON_UNESCAPED_UNICODE);

        if (is_string($encoded) && $encoded !== '') {
            $preview = $this->sanitizeResponsePreview($encoded, $apiKey);

            if ($preview !== '') {
                $failureMeta['response_preview'] = $preview;
            }
        }

        return $failureMeta;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{provider_message_code: ?string, provider_message: ?string}
     */
    private function extractProviderFields(array $body): array
    {
        $meta = is_array($body['meta'] ?? null) ? $body['meta'] : [];

        return [
            'provider_message_code' => $this->firstNonEmptyString([
                $meta['message_code'] ?? null,
                $meta['code'] ?? null,
                $body['message_code'] ?? null,
                $body['code'] ?? null,
            ]),
            'provider_message' => $this->firstNonEmptyString([
                $meta['message'] ?? null,
                $body['message'] ?? null,
                $body['error'] ?? null,
                $this->summarizeErrors($body['errors'] ?? null),
            ]),
        ];
    }

    /**
     * @param  list<mixed>  $candidates
     */
    private function firstNonEmptyString(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }

            if (is_int($candidate) || is_float($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }

    private function summarizeErrors(mixed $errors): ?string
    {
        if (is_string($errors) && $errors !== '') {
            return $errors;
        }

        if (! is_array($errors)) {
            return null;
        }

        $parts = [];

        foreach ($errors as $key => $error) {
            if (is_string($error) && $error !== '') {
                $parts[] = is_int($key) ? $error : "{$key}: {$error}";

                continue;
            }

            if (! is_array($error)) {
                continue;
            }

            foreach ($error as $item) {
                if (is_string($item) && $item !== '') {
                    $parts[] = is_int($key) ? $item : "{$key}: {$item}";
                }
            }
        }

        if ($parts === []) {
            return null;
        }

        return implode('; ', array_slice($parts, 0, 5));
    }

    private function sanitizeResponsePreview(string $body, ?string $apiKey): string
    {
        $preview = $body;

        if (is_string($apiKey) && $apiKey !== '') {
            $preview = str_replace($apiKey, '[REDACTED]', $preview);
        }

        $preview = preg_replace('/Api-Key\s*:\s*\S+/i', 'Api-Key: [REDACTED]', $preview) ?? $preview;
        $preview = preg_replace('/Authorization\s*:\s*\S+/i', 'Authorization: [REDACTED]', $preview) ?? $preview;
        $preview = preg_replace('/"Api-Key"\s*:\s*"[^"]*"/i', '"Api-Key": "[REDACTED]"', $preview) ?? $preview;
        $preview = preg_replace('/"api_key"\s*:\s*"[^"]*"/i', '"api_key": "[REDACTED]"', $preview) ?? $preview;
        $preview = preg_replace('/"Authorization"\s*:\s*"[^"]*"/i', '"Authorization": "[REDACTED]"', $preview) ?? $preview;
        $preview = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $preview) ?? $preview;

        return mb_substr($preview, 0, 200);
    }
}
