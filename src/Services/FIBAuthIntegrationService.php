<?php

    namespace FirstIraqiBank\FIBPaymentSDK\Services;

    use Exception;
    use Illuminate\Support\Facades\Http;

    class FIBAuthIntegrationService
    {
        protected string $account;

        /**
         * FIBAuthIntegrationService constructor.
         * Set the account based on configuration.
         */
        public function __construct()
        {
            $this->account = config('fib.auth_account', 'default');
        }

        /**
         * Retrieve the access token from the FIB Payment API.
         *
         * @return string
         * @throws Exception
         */
        public function getToken(): string
        {
            try {
                $response = retry(3, function () {
                    return Http::withOptions([
                        'verify' => false, // Disable SSL verification
                    ])->asForm()
                        ->withBasicAuth(
                            config("fib.{$this->account}.client_id"),
                            config("fib.{$this->account}.secret")
                        )->post(config('fib.login'), [
                            'grant_type' => config('fib.grant'),
                        ]);
                }, 100);


               if ($response->successful() && isset($response->json()['access_token'])) {
                    return $response->json()['access_token'];
                }


                throw new Exception('Failed to retrieve access token.');
            } catch (Exception $e) {

                throw $e;
            }
        }
    }
