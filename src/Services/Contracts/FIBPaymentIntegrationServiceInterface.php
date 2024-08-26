<?php

    namespace FirstIraqiBank\FIBPaymentSDK\Services\Contracts;

    interface FIBPaymentIntegrationServiceInterface
    {
        public function createPayment(int $amount, string|null $callback, string|null $description);
        public function checkPaymentStatus($paymentId);
        public function handleCallback(string $paymentId,  string $status);
        public function refund(string $paymentId);

    }
