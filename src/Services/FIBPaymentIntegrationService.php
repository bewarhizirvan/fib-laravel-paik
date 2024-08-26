<?php

namespace FirstIraqiBank\FIBPaymentSDK\Services;

use App\Models\FibPayment;
use App\Models\Refund;
use App\Services\Contracts\FIBPaymentIntegrationServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FIBPaymentIntegrationService implements FIBPaymentIntegrationServiceInterface
{
    protected string $baseUrl;

        public function __construct(
            private readonly FIBPaymentRepositoryService $fibPaymentRepository,
            private readonly FIBAuthIntegrationService   $fibAuthIntegrationService
        )
        {
            $this->baseUrl = config('fib.base_url');
        }

        /**
         * @throws Exception
         */
        private function postRequest(string $url, array $data)
        {
            $token = $this->fibAuthIntegrationService->getToken();
            $response = retry(3, function () use ($url, $data, $token) {
                return Http::withoutVerifying()->asJson()
                    ->withToken($token)
                    ->post($url, $data);
            }, 100);

            if (!$response->successful()) {
                Log::error('Failed to post request to FIB Payment API.', [
                    'url' => $url,
                    'data' => $data,
                    'response' => $response->body(),
                ]);
              //  throw new Exception('Failed to post request due to : '. $response->body());
            }
            return $response;
        }

        /**
         * @throws Exception
         */
        private function getRequest(string $url)
        {
            $token = $this->fibAuthIntegrationService->getToken();
            $response = retry(3, function () use ($url, $token) {
                return Http::withoutVerifying()
                    ->withToken($token)
                    ->get($url);
            }, 100);

            if (!$response->successful()) {
                Log::error('Failed to get request from FIB Payment API.', [
                    'url' => $url,
                    'response' => $response->body(),
                ]);
                throw new Exception('Failed to get request.');
            }

            return $response->json();
        }

        /**
         * @throws Exception
         */
        public function createPayment(int $amount, string $callback = null, string $description = null): Model|Builder
        {
            $data = $this->getPaymentData($amount, $callback, $description);
            $paymentData = $this->postRequest(url: "{$this->baseUrl}/payments", data: $data);
            return $this->fibPaymentRepository->createPayment($paymentData->json(), $amount);
        }

        /**
         * @throws Exception
         */
        public function checkPaymentStatus($paymentId)
        {
            $status = $this->getRequest(url: "{$this->baseUrl}/payments/{$paymentId}/status")['status'];
            $this->fibPaymentRepository->updatePaymentStatus($paymentId, $status);
            return $status;
        }

        public function handleCallback(string $paymentId,  string $status): void
        {
            $this->fibPaymentRepository->updatePaymentStatus(paymentId: $paymentId, status: $status);
        }

        public function getPaymentData(int $amount, string $callback = null, $description = null): array
        {
            return [
                'monetaryValue' => [
                    'amount' => $amount,
                    'currency' => config('fib.currency'),
                ],
                'statusCallbackUrl' => $callback ?? config('fib.callback'),
                'description' => $description?? '',
                'refundableFor' => config('fib.refundable_for'),
            ];
        }

        public function refund(string $paymentId): void
        {

            $response=  $this->postRequest(url: "{$this->baseUrl}/payments/{$paymentId}/refund", data: []);

            if ($response->status() == 202) {

                $refundData = [
                    'status' => Refund::SUCCESS,
                ];
                $this->fibPaymentRepository->updateOrCreateRefund(paymentId: $paymentId, refundData: $refundData);
                $this->fibPaymentRepository->updatePaymentStatus(paymentId: $paymentId, status: FibPayment::REFUNDED);
            }
            else {
                $refundData = [
                    'fib_trace_id' => $response['traceId'],
                    'refund_failure_reason' => implode(', ', array_column($response['errors'], 'code')),
                    'status' => Refund::FAILED,
                ];
                $this->fibPaymentRepository->updateOrCreateRefund(paymentId: $paymentId, refundData: $refundData);

            }

        }
    }
