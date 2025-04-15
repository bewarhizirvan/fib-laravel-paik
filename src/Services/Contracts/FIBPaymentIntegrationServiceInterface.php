<?php

    namespace FirstIraqiBank\FIBPaymentSDK\Services\Contracts;

    interface FIBPaymentIntegrationServiceInterface
    {
        public function createPayment(int $cid, int $amount, string|null $callback, string|null $description, string|null $redirectUri);
        public function checkPaymentStatus($paymentId);
        public function handleCallback(string $paymentId,  string $status);
        public function refund(string $paymentId);
        public function cancelPayment(string $paymentId);


    }
