<?php

namespace App\Services;

use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use RuntimeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Unified service for generating payment numbers.
 *
 * Uses MAX approach instead of COUNT to avoid issues:
 * - Race Condition
 * - Repeated deletion (rescheduling)
 *
 * Format: {PREFIX}-{YEAR}-{6-digit-sequence}
 * Example: COL-2025-000001, SUP-2025-000042
 */
class PaymentNumberGenerator
{
    /**
     * Available prefixes for each payment type.
     */
    public const PREFIX_COLLECTION = 'COL';

    public const PREFIX_SUPPLY = 'SUP';

    /**
     * Maximum retry attempts in case of number conflict.
     */
    private const MAX_RETRY_ATTEMPTS = 5;

    /**
     * Generate collection payment number.
     */
    public function generateCollectionPaymentNumber(): string
    {
        return $this->generate(
            self::PREFIX_COLLECTION,
            CollectionPayment::class
        );
    }

    /**
     * Generate supply payment number.
     */
    public function generateSupplyPaymentNumber(): string
    {
        return $this->generate(
            self::PREFIX_SUPPLY,
            SupplyPayment::class
        );
    }

    /**
     * Generate payment number based on type.
     *
     * @param  string  $prefix  Prefix (COL or SUP)
     * @param  string  $modelClass  Model class
     * @return string Unique payment number
     */
    public function generate(string $prefix, string $modelClass): string
    {
        $year = date('Y');
        $pattern = $prefix.'-'.$year.'-%';

        // Use MAX instead of COUNT
        // This ensures no number repetition even after deletion
        $lastNumber = $this->getLastSequenceNumber($modelClass, $pattern, $prefix, $year);
        $nextNumber = $lastNumber + 1;

        return $this->formatPaymentNumber($prefix, $year, $nextNumber);
    }

    /**
     * Get last sequence number from database.
     *
     * @param  string  $modelClass  Model class
     * @param  string  $pattern  LIKE search pattern
     * @param  string  $prefix  Prefix
     * @param  string  $year  Year
     * @return int Last sequence number (0 if none exists)
     */
    private function getLastSequenceNumber(string $modelClass, string $pattern, string $prefix, string $year): int
    {
        /** @var Model $model */
        $model = new $modelClass;
        $table = $model->getTable();

        // Use raw query for efficient MAX retrieval
        // Search for last sequence number with required pattern
        $result = DB::table($table)
            ->where('payment_number', 'like', $pattern)
            ->selectRaw('MAX(CAST(SUBSTRING(payment_number, ?) AS UNSIGNED)) as max_seq', [
                strlen($prefix.'-'.$year.'-') + 1,
            ])
            ->first();

        return $result->max_seq ?? 0;
    }

    /**
     * Format payment number with required format.
     *
     * @param  string  $prefix  Prefix
     * @param  string  $year  Year
     * @param  int  $sequence  Sequence number
     * @return string Formatted payment number
     */
    private function formatPaymentNumber(string $prefix, string $year, int $sequence): string
    {
        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }

    /**
     * Generate payment number with retry on conflict.
     *
     * This function is used when uniqueness guarantee is needed even in
     * extreme Race Condition cases.
     *
     * @param  string  $prefix  Prefix
     * @param  string  $modelClass  Model class
     * @return string Unique payment number
     *
     * @throws RuntimeException If all attempts fail
     */
    public function generateWithRetry(string $prefix, string $modelClass): string
    {
        $attempts = 0;
        $year = date('Y');

        while ($attempts < self::MAX_RETRY_ATTEMPTS) {
            $paymentNumber = $this->generate($prefix, $modelClass);

            // Verify number doesn't exist (additional protection)
            /** @var Model $model */
            $model = new $modelClass;
            $exists = $model->newQuery()
                ->where('payment_number', $paymentNumber)
                ->exists();

            if (! $exists) {
                return $paymentNumber;
            }

            $attempts++;

            // In case of conflict, wait briefly before retry
            // This only happens in rare Race Condition cases
            usleep(random_int(1000, 10000)); // Wait 1-10 milliseconds
        }

        throw new RuntimeException(
            "Failed to generate unique payment number after {$attempts} attempts. ".
            'Please try again or contact technical support.'
        );
    }

    /**
     * Extract sequence number from existing payment number.
     *
     * @param  string  $paymentNumber  Payment number
     * @return int|null Sequence number or null if format is invalid
     */
    public function extractSequenceNumber(string $paymentNumber): ?int
    {
        // Pattern: PREFIX-YEAR-SEQUENCE
        if (preg_match('/^(COL|SUP)-(\d{4})-(\d+)$/', $paymentNumber, $matches)) {
            return (int) $matches[3];
        }

        return null;
    }

    /**
     * Validate payment number format.
     *
     * @param  string  $paymentNumber  Payment number
     * @return bool True if format is valid
     */
    public function isValidFormat(string $paymentNumber): bool
    {
        return (bool) preg_match('/^(COL|SUP)-\d{4}-\d{6}$/', $paymentNumber);
    }

    /**
     * Get prefix from payment number.
     *
     * @param  string  $paymentNumber  Payment number
     * @return string|null Prefix or null
     */
    public function extractPrefix(string $paymentNumber): ?string
    {
        if (preg_match('/^(COL|SUP)-/', $paymentNumber, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get year from payment number.
     *
     * @param  string  $paymentNumber  Payment number
     * @return string|null Year or null
     */
    public function extractYear(string $paymentNumber): ?string
    {
        if (preg_match('/^(?:COL|SUP)-(\d{4})-/', $paymentNumber, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
