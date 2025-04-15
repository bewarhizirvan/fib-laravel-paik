<?php

namespace FirstIraqiBank\FIBPaymentSDK\Services;

use Exception;
use FirstIraqiBank\FIBPaymentSDK\Model\FibPayment;
use FirstIraqiBank\FIBPaymentSDK\Services\Contracts\FIBPaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class FIBPaymentRepositoryService implements FIBPaymentRepositoryInterface
{
    /**
     * Create a new payment record.
     *
     * @param array $paymentData
     * @param int $amount
     * @return Builder|Model
     */
    public function createPayment(array $paymentData, int $amount)
    {
        return FibPayment::query()->create([
            'cid' => $paymentData['cid'],
            'fib_payment_id' => $paymentData['paymentId'],
            'readable_code' => $paymentData['readableCode'],
            'personal_app_link' => $paymentData['personalAppLink'],
            'status' => FibPayment::PENDING,
            'amount' => $amount,
            'valid_until' => $paymentData['validUntil'],
        ]);
    }

    /**
     * Retrieve a payment by its FIB payment ID.
     *
     * @param string $paymentId
     * @return Model
     * @throws ModelNotFoundException
     */
    public function getPaymentByFibId(string $paymentId): Model
    {
        return FibPayment::query()->where('fib_payment_id', $paymentId)->firstOrFail();
    }

    /**
     * Retrieve a payment by its ID.
     *
     * @param int $paymentId
     * @return Model|null
     */
    public function getPaymentById(int $paymentId): ?Model
    {
        return FibPayment::query()->find($paymentId);
    }

    /**
     * Retrieve payments by their status.
     *
     * @param array $statuses
     * @return Collection
     */
    public function getPaymentsByStatus(array $statuses): Collection
    {
        return FibPayment::query()->whereIn('status', $statuses)->where('created_at', '<', now()->subMinutes(5))->get();
    }

    /**
     * Update the status of a payment.
     *
     * @param string $paymentId
     * @param string $status
     * @return void
     */
    public function updatePaymentStatus(string $paymentId, string $status): void
    {
        try {
            $this->getPaymentByFibId($paymentId)->update(['status' => $status]);
        } catch (ModelNotFoundException $e) {
            Log::error('Payment not found', ['paymentId' => $paymentId, 'exception' => $e]);
            throw new Exception("Payment with ID {$paymentId} not found.");
        }
    }

    /**
     * Retrieve the purchase associated with a payment.
     *
     * @param int $paymentId
     * @return Model|null
     */
    public function getPurchase(int $paymentId): ?Model
    {
        return $this->getPaymentById($paymentId)->purchase()->first();
    }

    /**
     * Update or create a refund record.
     *
     * @param string $paymentId
     * @param array $refundData
     * @return void
     */
    public function updateOrCreateRefund(string $paymentId, array $refundData): void
    {
        $fibPayment = $this->getPaymentByFibId($paymentId);
        $fibPayment->refund()->updateOrCreate(['payment_id' => $fibPayment->id], $refundData);
    }
}
