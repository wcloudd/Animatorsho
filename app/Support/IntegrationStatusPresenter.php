<?php

namespace App\Support;

class IntegrationStatusPresenter
{
    /**
     * @return array{
     *     zarinpalConfigured: bool,
     *     farazSmsConfigured: bool,
     *     spotPlayerConfigured: bool
     * }
     */
    public function toAdminArray(): array
    {
        return [
            'zarinpalConfigured' => $this->isZarinpalConfigured(),
            'farazSmsConfigured' => $this->isFarazSmsConfigured(),
            'spotPlayerConfigured' => $this->isSpotPlayerConfigured(),
        ];
    }

    /**
     * Read-only card-to-card operational display for admin site settings.
     *
     * @return array{
     *     configured: bool,
     *     source: string,
     *     cardNumber: string,
     *     cardOwnerName: string
     * }
     */
    public function cardToCardToAdminArray(): array
    {
        $cardNumber = $this->cardToCardNumber();
        $cardOwnerName = $this->cardToCardOwnerName();

        return [
            'configured' => $this->isCardToCardConfigured(),
            'source' => '.env / config',
            'cardNumber' => $cardNumber ?? 'ثبت نشده',
            'cardOwnerName' => $cardOwnerName ?? 'ثبت نشده',
        ];
    }

    public function isCardToCardConfigured(): bool
    {
        $cardNumber = $this->cardToCardNumber();
        $cardOwnerName = $this->cardToCardOwnerName();

        return is_string($cardNumber) && $cardNumber !== ''
            && is_string($cardOwnerName) && $cardOwnerName !== '';
    }

    public function cardToCardNumber(): ?string
    {
        $value = config('card_to_card.card_number');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function cardToCardOwnerName(): ?string
    {
        $value = config('card_to_card.card_owner_name');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function isZarinpalConfigured(): bool
    {
        $merchantId = config('zarinpal.merchant_id');

        return is_string($merchantId) && $merchantId !== '';
    }

    public function isFarazSmsConfigured(): bool
    {
        return $this->farazSmsCredentialsPresent();
    }

    public function isSpotPlayerConfigured(): bool
    {
        if (! filter_var(config('spotplayer.enabled'), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $apiKey = config('spotplayer.api_key');

        return is_string($apiKey) && $apiKey !== '';
    }

    private function farazSmsCredentialsPresent(): bool
    {
        $config = config('sms.providers.farazsms');

        if (! is_array($config)) {
            return false;
        }

        $apiKey = $config['api_key'] ?? null;
        $sender = $config['sender'] ?? null;

        return is_string($apiKey) && $apiKey !== ''
            && is_string($sender) && $sender !== '';
    }
}
