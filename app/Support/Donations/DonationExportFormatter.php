<?php

namespace App\Support\Donations;

use DateTimeInterface;

class DonationExportFormatter
{
    /**
     * Update the provided donation payload so that the actual collection date respects
     * the export business rules around InstaPay donations and missing actual dates.
     *
     * @param  array|object  $donation
     * @return array|object
     */
    public static function updateActualCollectionDate(array|object $donation): array|object
    {
        $resolved = static::resolveActualCollectionDate($donation);

        if (is_array($donation)) {
            $donation['actual_collection_date'] = $resolved;

            return $donation;
        }

        $donation->actual_collection_date = $resolved;

        return $donation;
    }

    /**
     * Resolve the value that should be used for the actual collection date column when exporting.
     */
    public static function resolveActualCollectionDate(array|object $donation)
    {
        $initial = static::normaliseDateValue(static::value($donation, 'initial_collection_date'));
        $actual = static::normaliseDateValue(static::value($donation, 'actual_collection_date'));
        $paymentChannel = static::detectPaymentChannel($donation);

        if ($initial === null && $actual === null) {
            return null;
        }

        if (static::isInstaPay($paymentChannel)) {
            return $initial ?? $actual;
        }

        if ($actual === null || $actual === '') {
            return $initial;
        }

        return $actual;
    }

    /**
     * Try to detect the channel/method used to complete the donation payment.
     */
    protected static function detectPaymentChannel(array|object $donation): ?string
    {
        $candidates = [
            'payment_method',
            'payment_channel',
            'payment_gateway',
            'donation_channel',
            'donation_method',
        ];

        foreach ($candidates as $candidate) {
            $value = static::value($donation, $candidate);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Determine whether the provided channel represents an InstaPay donation.
     */
    protected static function isInstaPay(?string $channel): bool
    {
        if ($channel === null) {
            return false;
        }

        $normalised = strtolower(preg_replace('/[^a-z0-9]/i', '', $channel));

        return $normalised !== '' && str_contains($normalised, 'instapay');
    }

    /**
     * Extract a value from either an array or an object.
     */
    protected static function value(array|object $source, string $key): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? null;
        }

        return $source->{$key} ?? null;
    }

    /**
     * Convert empty values into null while keeping \DateTimeInterface instances intact.
     */
    protected static function normaliseDateValue(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $value;
        }

        return $value ?: null;
    }
}
