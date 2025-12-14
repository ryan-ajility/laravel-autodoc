<?php

namespace App\Contracts;

/**
 * Payment processor contract.
 *
 * Defines the standard interface for all payment processing implementations.
 * Any class that processes payments should implement this interface to ensure
 * consistency across different payment gateways and processors.
 */
interface PaymentProcessorInterface
{
    /**
     * Process a payment transaction.
     *
     * Charges the customer's payment method and creates a transaction record.
     * Returns detailed information about the processed transaction.
     *
     * @param  float  $amount  The amount to charge in the specified currency
     * @param  string  $currency  The ISO 4217 currency code (e.g., USD, EUR, GBP)
     * @param  array  $metadata  Additional metadata to attach to the transaction
     * @return array The transaction result including transaction ID and status
     */
    public function processPayment(float $amount, string $currency = 'USD', array $metadata = []): array;

    /**
     * Refund a previous payment transaction.
     *
     * @param  string  $transactionId  The ID of the transaction to refund
     * @param  float|null  $amount  The amount to refund (null for full refund)
     * @return bool True if the refund was successful
     */
    public function refundPayment(string $transactionId, ?float $amount = null): bool;
}
