<?php

namespace App\Traits;

use App\Services\PaymentNumberGenerator;

/**
 * Trait for auto-generating payment numbers.
 *
 * Used in CollectionPayment and SupplyPayment models
 * to unify payment number generation logic.
 *
 * Requirements:
 * - The model must have a payment_number column
 * - The model must define PAYMENT_NUMBER_PREFIX constant
 */
trait HasPaymentNumber
{
    /**
     * Register model events for auto-generating payment number.
     */
    protected static function bootHasPaymentNumber(): void
    {
        static::creating(function ($model) {
            // Generate payment number if not present
            if (empty($model->payment_number)) {
                $model->payment_number = static::generateNewPaymentNumber();
            }
        });
    }

    /**
     * Generate a new payment number.
     *
     * @return string Unique payment number
     */
    public static function generateNewPaymentNumber(): string
    {
        $generator = app(PaymentNumberGenerator::class);

        return $generator->generateWithRetry(
            static::getPaymentNumberPrefix(),
            static::class
        );
    }

    /**
     * Get the prefix for the payment type.
     *
     * This method must be defined in the model using this Trait.
     *
     * @return string The prefix (COL or SUP)
     */
    abstract public static function getPaymentNumberPrefix(): string;

    /**
     * Validate payment number format.
     *
     * @return bool True if format is valid
     */
    public function hasValidPaymentNumberFormat(): bool
    {
        if (empty($this->payment_number)) {
            return false;
        }

        $generator = app(PaymentNumberGenerator::class);

        return $generator->isValidFormat($this->payment_number);
    }

    /**
     * Get sequence number from payment number.
     *
     * @return int|null Sequence number or null
     */
    public function getPaymentSequenceNumber(): ?int
    {
        if (empty($this->payment_number)) {
            return null;
        }

        $generator = app(PaymentNumberGenerator::class);

        return $generator->extractSequenceNumber($this->payment_number);
    }

    /**
     * Get payment year from payment number.
     *
     * @return string|null Year or null
     */
    public function getPaymentYear(): ?string
    {
        if (empty($this->payment_number)) {
            return null;
        }

        $generator = app(PaymentNumberGenerator::class);

        return $generator->extractYear($this->payment_number);
    }
}
