<?php

namespace FirstIraqiBank\FIBPaymentSDK\Services;

use App\Models\FibPayment;
use App\Services\Contracts\FIBPaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class FIBPaymentRepositoryService implements FIBPaymentRepositoryInterface
{
    public function createPayment(array $paymentData, int $amount): Builder|Model
    {
        return  FibPayment::query()->create([
            'fib_payment_id' => $paymentData['paymentId'],
            'readable_code' => $paymentData['readableCode'],
            'personal_app_link' => $paymentData['personalAppLink'],
            'status' => FibPayment::PENDING,
            'amount' => $amount,
            'valid_until' => $paymentData['validUntil'],
        ]);
    }

    public function getPaymentByFibId(string $paymentId): Model|Collection|Builder|array|null
    {
        $payment = FibPayment::query()->where('fib_payment_id', $paymentId)->firstOrFail();

        if (!$payment) {
            Log::warning("Payment not found for callback ID: {$paymentId}");
            throw new ModelNotFoundException("Payment with FIB payment ID {$paymentId} not found");
        }

        return $payment;
    }

    public function getPaymentById(int $paymentId): Model|Collection|Builder|array|null
    {
        return FibPayment::query()->find($paymentId);
    }

    public function getPaymentsByStatus(array $statuses): Model|Collection|Builder|array|null
    {
        return FibPayment::query()->whereIn('status', $statuses)
            ->where('created_at', '<', now()->subMinutes(5))
            ->get();
    }

    public function updatePaymentStatus(string $paymentId, string $status): void
    {
        // Update the payment status in the database
        $this->getPaymentByFibId($paymentId)->update(['status' => $status]);
    }

    public function getPurchase(int $paymentId)
    {

        return $this->getPaymentById($paymentId)->purchase()->first();
    }

    public function updateOrCreateRefund(string $paymentId, array $refundData)
    {
        $fibPayment =  $this->getPaymentByFibId($paymentId);
        $fibPayment->refund()->updateOrCreate(['payment_id'=>$fibPayment->id], $refundData);
    }

}
