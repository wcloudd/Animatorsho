<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Pure calculator for installment pricing. Reads the versioned rules from
 * config/installment.php and produces a financial snapshot for a given cash
 * price + term. Callers must persist the returned snapshot on the order/payment
 * and never recalculate existing orders from changed config values.
 */
class InstallmentPricing
{
    /**
     * @return array{
     *     term: string,
     *     months: int,
     *     down_payment_percent: int,
     *     cash_price_toman: int,
     *     extra_amount_toman: int,
     *     installment_total_toman: int,
     *     down_payment_toman: int,
     *     remaining_toman: int
     * }
     */
    public static function calculate(int $cashPriceToman, string $term): array
    {
        $termConfig = self::termConfig($term);

        $percent = self::downPaymentPercent();
        $extraAmount = (int) $termConfig['extra_toman'];
        $months = (int) $termConfig['months'];

        $installmentTotal = $cashPriceToman + $extraAmount;
        $downPayment = (int) round($installmentTotal * $percent / 100);
        $remaining = $installmentTotal - $downPayment;

        return [
            'term' => $term,
            'months' => $months,
            'down_payment_percent' => $percent,
            'cash_price_toman' => $cashPriceToman,
            'extra_amount_toman' => $extraAmount,
            'installment_total_toman' => $installmentTotal,
            'down_payment_toman' => $downPayment,
            'remaining_toman' => $remaining,
        ];
    }

    public static function downPaymentPercent(): int
    {
        return (int) config('installment.down_payment_percent', 40);
    }

    /**
     * @return list<string>
     */
    public static function availableTerms(): array
    {
        return array_keys(self::terms());
    }

    public static function label(string $term): ?string
    {
        $terms = self::terms();

        if (! isset($terms[$term]) || ! is_array($terms[$term])) {
            return null;
        }

        $label = $terms[$term]['label'] ?? null;

        return is_string($label) ? $label : null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function termConfig(string $term): array
    {
        $terms = self::terms();

        if (! isset($terms[$term]) || ! is_array($terms[$term])) {
            throw new InvalidArgumentException("Unknown installment term [{$term}].");
        }

        return $terms[$term];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function terms(): array
    {
        $terms = config('installment.terms', []);

        return is_array($terms) ? $terms : [];
    }
}
