Omnipay: Redsys (+bizum) driver
===============

| Version | depends on guzzlehttp/psr7|
|---------|----------------|
| 1.x     | 1.x            |
| 2.x     | 2.x / php >7.4.5           |


**RedSys driver for the Omnipay PHP payment processing library**

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP >7.1 This package implements RedSys (formerly Sermepa) support for Omnipay.

This is an improved version of the original package by [nazka](nazka/sermepa-omnipay) which seems to be stalled since
years
ago.

#### This package features:

- Support for Omnipay 3.X
- Support for Redsys 3DS2
- Support for Redsys Bizum
- Dictionary of Redsys payment methods
- Dictionary of transaction types
- Transaction compatibility with payment method is checked to ensure validity of request
- JSON dictionary with all error codes
- Better docs and how it works explanations
- Dictionary with all languages supported by Redsys

Installation
------------

Via [Composer](http://getcomposer.org/). To install, simply run:

```sh
composer require eseperio/omnipay-redsys
```

### How it works

In order to understand the code included within this library, you should check the docs
page [how it works](docs/how-it-works.md)

### List of errors

Redsys has a list of errors that can be returned by the gateway. You can find the list of
errors [here](docs/error-codes.md). Also the whole list is available in JSON format [here](src/utils/error-codes.json)

Basic Usage
-----------
Create a gateway instance for RedSys:

```php
$gateway = Omnipay::create('Redsys');
```

Now define all the parameters needed by the gateway:

```php
$gateway->setMerchantCode('my_merchant_code');
$gateway->setMerchantKey('my_merchant_key');
$gateway->setTransactionType(\Omnipay\Redsys\Dictionaries\TransactionTypes::AUTHORIZATION);
$gateway->setCurrency('my_currency');
$gateway->setMerchantName('my_shop_name');
$gateway->setMerchantPaymethod('my_pay_method');
$gateway->setSignatureMode('my_signature_mode');
$gateway->setConsumerLanguage(0);
$gateway->setTerminal('my_terminal');
```

`setMerchantUrl` is the url where the gateway will send the response of the transaction. This url must be accessible

Next, create a request, which can be either a purchase (common) or an authorize request:

```php
$request = $gateway->purchase()
            // Define the urls
            ->setCancelUrl('my_cancel_url')
            ->setReturnUrl('my_return_url')
            ->setMerchantUrl('my_merchant_url')
            // Define the transaction
            ->setTitular('my_company_name')
            ->setAmount('my_total_amount')
            ->setTransactionId('my_transaction_id')
            ->setOrder('my_order_id')
            ->setTransactionReference('my_transaction_reference')
            ->setDescription('my_order_description')
            
            // Optional. 
            ->setConsumerLanguage(\Omnipay\Redsys\Dictionaries\Languages::SPANISH);

    ],
]);
```

> **Important**: Redsys expects the transactionId to be an integer, and the amount to be an integer where the last 2
> digits are decimals.

Omnipay has many methods to set the amount, but this library has the overridden method `setAmount` to ensure that the
amount is an integer and the last 2 digits are decimals. We highly recommend using this method unless you are using
[Money library](moneyphp/money), in that case use `setMoney(Money $money)`

When you use `setAmount()`, the amount is formatted with `number_format($amount, 2, '', '')`.
You still can
use [`setAmountInteger()`](https://github.com/thephpleague/omnipay-common/blob/e1ebc22615f14219d31cefdf62d7036feb228b1c/src/Common/Message/AbstractRequest.php#L355)
to set the amount as an integer.

## Receiving the payment response

>Previously, a fake request was created to simulate the response from Redsys, but since Omnipay 3.0, acceptNotification must be used to receive the response from the gateway.

#### New way (acceptNotification)

 ```php
 $gateway = Omnipay::create('Redsys');
 $gateway->initialize([
    'merchantId' => '123456789',
    'merchantKey' => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
    ...
 ]);
 $notification = $gateway->acceptNotification();
 if ($notification->getTransactionStatus() === NotificationInterface::STATUS_COMPLETED) {
    // Payment was successful
   $transactionReference = $notification->getTransactionReference();
  // Do your stuff here
 } else {
   // Payment failed
 $message = $notification->getMessage();
 // Do your stuff here
 }
 ```

#### Old way (completePurchase)


Now, on the route you provided as `merchantUrl` you can receive the response from Redsys:

```php 
$gateway = Omnipay::create('Sermepa');
$response = $gateway->completePurchase()->send();
if($response->isSuccessful()){
    // The payment was successful
    // Confirm your order or other stuff
    
    $orderId = $response->getTransactionId();
    
    // ...
}
```

### Using BIZUM

All the params are shared between card and bizum, so you can use the same gateway instance to create a bizum request.
The only difference is the payment method:

```php
// Set the payment method to BIZUM
$request->setPayMethod(PayMethods::PAY_METHOD_BIZUM);
// Bizum is only compatible with AUTHORIZATION transactions
$gateway->setTransactionType(TransactionTypes::AUTHORIZATION);
```

For other general usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay)
repository.


Upgrade to Omnipay 3.X
-----------

Changes for use with Omnipay 3.0

- Currency: Use the code of ISO-4217 (https://en.wikipedia.org/wiki/ISO_4217#Active_codes) instead a number. ('
  EUR' => '978')


