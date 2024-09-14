# FIB Laravel Payment SDK

The FIB Laravel Payment SDK provides a seamless integration with the FIB payment system for Laravel applications, enabling secure and efficient payment transactions and refund handling.

**Table of Contents**
- [Features](#features)
- [Installation](#installation)
    - [Composer Installation](#composer-installation)
    - [Alternative Installation (Without Composer)](#alternative-installation-without-composer)
- [Configuration](#configuration)
- [Usage](#usage)
    - [Creating a Payment](#creating-a-payment)
    - [Checking Payment Status](#checking-payment-status)
    - [Refunding a Payment](#refunding-a-payment)
    - [Cancelling a Payment](#cancelling-a-payment)
    - [Handling Payment Callbacks](#handling-payment-callbacks)
- [FIB Payment Documentation](#fib-payment-documentation)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)
- [Acknowledgments](#acknowledgments)
- [Versioning](#versioning)
- [FAQ](#faq)

## Features

- **Payment Transactions**: Facilitate secure payments through the FIB payment system directly in your Laravel application.
- **Refund Processing**: Manage refunds through the FIB payment system with ease.
- **Payment Status Checking**: Retrieve the status of payments to ensure proper transaction tracking.
- **Payment Cancellation**: Cancel payments as needed through the FIB payment system.

## Installation

To integrate the SDK into your Laravel project, install it via Composer:

```bash
composer require First-Iraqi-Bank/fib-laravel-payment-sdk
```

### Alternative Installation (Without Composer)
If you prefer not to use Composer, follow these steps:

- **Clone the Repository**: Clone the FIB Payment SDK repository:

  ```bash
  git clone https://github.com/First-Iraqi-Bank/fib-laravel-payment-sdk.git
  ```

- **Include in Your Project**: Move or copy the cloned `fib-laravel-payment-sdk` directory into your Laravel project.

- **Autoloading**: Ensure that the `src` directory of the SDK is included in your `composer.json` autoloader configuration if not using Composer:

  ```json
  {
      "autoload": {
          "psr-4": {
              "FirstIraqiBank\\FIBPaymentSDK\\": "path/to/fib-laravel-payment-sdk/src"
          }
      }
  }
  ```

- **Usage**: After including the SDK, use its classes and functionality in your Laravel application.

### Configuration

To customize the settings for the FIB Laravel Payment SDK, you need to publish the configuration file:

```bash
php artisan vendor:publish --tag=fib-payment-sdk-config
```


Add the following environment variables to your `.env` file:

- `FIB_API_KEY`: Your FIB payment API key.
- `FIB_API_SECRET`: Your FIB payment API secret.
- `FIB_BASE_URL`: The base URL for the FIB payment API (default: https://api.fibpayment.com).
- `FIB_GRANT_TYPE`: The grant type for authentication (default: client_credentials).
- `FIB_REFUNDABLE_FOR`: The period for which transactions can be refunded (default: P7D).
- `FIB_CURRENCY`: The currency used for transactions (default: IQD).
- `FIB_CALLBACK_URL`: The callback URL for payment notifications.
- `FIB_ACCOUNT`: The FIB payment account identifier.

### Usage of the SDK

#### Ensure Dependencies are Installed:
Install required dependencies using Composer:

```bash
composer install
```

#### Set Up Environment Variables:
Create a `.env` file in the root directory of your Laravel project and set the necessary environment variables.

#### Creating a Payment

Here's an example of how to create a payment:

```php
<?php

use FirstIraqiBank\FIBPaymentSDK\Services\FIBAuthIntegrationService;
use FirstIraqiBank\FIBPaymentSDK\Services\FIBPaymentIntegrationService;

// Initialize the authentication service
$authService = new FIBAuthIntegrationService();

// Initialize the payment integration service
$paymentService = new FIBPaymentIntegrationService($authService);

try {
    // Create a new payment
    $paymentResponse = $paymentService->createPayment(1000, 'http://localhost/callback', 'Test payment description');
    $paymentData = json_decode($paymentResponse->getBody(), true);
    
    // Return the payment ID
    return $paymentData['paymentId'];
} catch (Exception $e) {
    throw new Exception("Error creating payment: " . $e->getMessage());
}
```

#### Checking the Payment Status

To check the status of a payment:

```php
<?php

use FirstIraqiBank\FIBPaymentSDK\Services\FIBAuthIntegrationService;
use FirstIraqiBank\FIBPaymentSDK\Services\FIBPaymentIntegrationService;

// Initialize the authentication service
$authService = new FIBAuthIntegrationService();

// Initialize the payment integration service
$paymentService = new FIBPaymentIntegrationService($authService);

try {
    $paymentId = 'your_payment_id'; // Retrieve from your storage
    $response = $paymentService->checkPaymentStatus($paymentId);
    echo "Payment Status: " . $response['status'] ?? null;
} catch (Exception $e) {
    echo "Error checking payment status: " . $e->getMessage();
}
```

#### Refunding a Payment

To process a refund:

```php
<?php

use FirstIraqiBank\FIBPaymentSDK\Services\FIBAuthIntegrationService;
use FirstIraqiBank\FIBPaymentSDK\Services\FIBPaymentIntegrationService;

// Initialize the authentication service
$authService = new FIBAuthIntegrationService();

// Initialize the payment integration service
$paymentService = new FIBPaymentIntegrationService($authService);

try {
    $paymentId = 'your_payment_id'; // Retrieve from your storage
    $response = $paymentService->refund($paymentId);
    echo "Refund Payment Status: " . $response['status_code'];
} catch (Exception $e) {
    echo "Error Refunding payment: " . $e->getMessage();
}
```

#### Cancelling a Payment

To cancel a payment:

```php
<?php

use FirstIraqiBank\FIBPaymentSDK\Services\FIBAuthIntegrationService;
use FirstIraqiBank\FIBPaymentSDK\Services\FIBPaymentIntegrationService;

// Initialize the authentication service
$authService = new FIBAuthIntegrationService();

// Initialize the payment integration service
$paymentService = new FIBPaymentIntegrationService($authService);

try {
    $paymentId = 'your_payment_id'; // Retrieve from your storage
    $response = $paymentService->cancel($paymentId);
    if (in_array($response->getStatusCode(), [200, 201, 202, 204])) {
        echo "Cancel Payment Status: Successful";
    } else {
        echo "Cancel Payment Status: Failed with status code " . $response->getStatusCode();
    }
} catch (Exception $e) {
    echo "Error Cancelling payment: " . $e->getMessage();
}
```

#### Handling Payment Callbacks

To handle payment callbacks, create a route and controller method:

```php
// web.php or api.php
Route::post('/callback', [PaymentController::class, 'handleCallback']);

// PaymentController.php
public function handleCallback(Request $request)
{
    $payload = $request->all();

    $paymentId = $payload['id'] ?? null;
    $status = $payload['status'] ?? null;

    if (!$paymentId || !$status) {
        return response()->json(['error' => 'Invalid callback payload'], 400);
    }

    try {
        // Implement your callback handling logic
        return response()->json(['message' => 'Callback processed successfully']);
    } catch (Exception $e) {
        return response()->json(['error' => 'Failed to process callback: ' . $e->getMessage()], 500);
    }
}
```

### FIB Payment Documentation

For detailed documentation on FIB Online Payment, refer to the [full documentation](https://documenter.getpostman.com/view/18377702/UVCB93tc).

### Testing

Run tests using PHPUnit:

```bash
vendor/bin/phpunit --testdox
```

### Contributing

Contributions are welcome! Please read `CONTRIBUTING.md` for details on our code of conduct and the process for submitting pull requests.

### License

This project is licensed under the MIT License. See the [LICENSE.md](LICENSE.md) file for details.

### Support

For support, please contact support@fib-payment.com or visit our website.

### Acknowledgments

Thanks to the FIB Payment development team for their contributions. This SDK uses the cURL library for API requests.

### Versioning

We use semantic versioning (SemVer) principles. For available versions, see the tags on this repository.

### FAQ

**Q: How do I get an API key for the FIB Payment system?**

A: Contact our support team at support@fib-payment.com to request an API key.

**Q: Can I use this SDK in a production environment?**

A: Yes, the SDK is designed for production, but ensure it is configured correctly and you have the necessary credentials.
