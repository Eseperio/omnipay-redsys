<?php

namespace Omnipay\Redsys\Tests;

use Omnipay\Redsys\Dictionaries\PayMethods;
use Omnipay\Redsys\Dictionaries\TransactionTypes;
use Omnipay\Redsys\Exception\BadPayMethodException;
use Omnipay\Redsys\Gateway;
use Omnipay\Redsys\Message\AcceptNotification;
use Omnipay\Redsys\Message\AuthorizeRequest;
use Omnipay\Redsys\Message\CallbackResponse;
use Omnipay\Redsys\Message\CompleteAuthorizeRequest;
use Omnipay\Redsys\Message\CompletePurchaseRequest;
use Omnipay\Redsys\Message\PurchaseRequest;
use Symfony\Component\HttpFoundation\Request;

class GatewayTest extends RedsysTestCase
{
    /** @var Gateway */
    private $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new Gateway();
        $this->gateway->initialize([
            'merchantKey' => self::MERCHANT_KEY,
            'merchantCode' => self::MERCHANT_CODE,
            'terminal' => self::TERMINAL,
        ]);
    }

    public function testGetName()
    {
        $this->assertEquals('Redsys', $this->gateway->getName());
    }

    public function testDefaultParameters()
    {
        $defaults = $this->gateway->getDefaultParameters();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('consumerLanguage', $defaults);
        $this->assertArrayHasKey('currency', $defaults);
        $this->assertArrayHasKey('terminal', $defaults);
        $this->assertArrayHasKey('merchantURL', $defaults);
        $this->assertArrayHasKey('merchantName', $defaults);
        $this->assertArrayHasKey('transactionType', $defaults);
        $this->assertArrayHasKey('signatureMode', $defaults);
        $this->assertArrayHasKey('testMode', $defaults);
        $this->assertArrayHasKey('payMethods', $defaults);

        $this->assertEquals('001', $defaults['consumerLanguage']);
        $this->assertEquals('EUR', $defaults['currency']);
        $this->assertEquals('001', $defaults['terminal']);
        $this->assertEquals(TransactionTypes::AUTHORIZATION, $defaults['transactionType']);
        $this->assertEquals('simple', $defaults['signatureMode']);
        $this->assertFalse($defaults['testMode']);
        $this->assertEquals(PayMethods::PAY_METHOD_CARD, $defaults['payMethods']);
    }

    public function testSetAndGetMerchantKey()
    {
        $this->gateway->setMerchantKey('newkey123');

        $this->assertEquals('newkey123', $this->gateway->getParameter('merchantKey'));
    }

    public function testSetAndGetMerchantCode()
    {
        $this->gateway->setMerchantCode('123456789');

        $this->assertEquals('123456789', $this->gateway->getParameter('merchantCode'));
    }

    public function testSetCurrencyMerchant()
    {
        $this->gateway->setCurrencyMerchant('USD');

        $this->assertEquals('USD', $this->gateway->getParameter('merchantCurrency'));
    }

    public function testSetIdentifierOnGateway()
    {
        $this->gateway->setIdentifier('REQUIRED');

        $this->assertEquals('REQUIRED', $this->gateway->getParameter('identifier'));
    }

    public function testSetMerchantNameOnGateway()
    {
        $this->gateway->setMerchantName('My Commerce');

        $this->assertEquals('My Commerce', $this->gateway->getParameter('merchantName'));
    }

    public function testSetAndGetMerchantURL()
    {
        $this->gateway->setMerchantURL('https://example.com/notify');

        $this->assertEquals('https://example.com/notify', $this->gateway->getParameter('merchantURL'));
    }

    public function testSetAndGetTerminal()
    {
        $this->gateway->setTerminal('002');

        $this->assertEquals('002', $this->gateway->getParameter('terminal'));
    }

    public function testSetAndGetSignatureMode()
    {
        $this->gateway->setSignatureMode('extended');

        $this->assertEquals('extended', $this->gateway->getParameter('signatureMode'));
    }

    public function testSetAndGetConsumerLanguage()
    {
        $this->gateway->setConsumerLanguage('002');

        $this->assertEquals('002', $this->gateway->getParameter('consumerLanguage'));
    }

    public function testSetAndGetReturnUrl()
    {
        $this->gateway->setReturnUrl('https://example.com/return');

        $this->assertEquals('https://example.com/return', $this->gateway->getParameter('returnUrl'));
    }

    public function testSetAndGetCancelUrl()
    {
        $this->gateway->setCancelUrl('https://example.com/cancel');

        $this->assertEquals('https://example.com/cancel', $this->gateway->getParameter('cancelUrl'));
    }

    public function testSetAndGetIdentifier()
    {
        $this->gateway->setIdentifier('REQUIRED');

        $this->assertEquals('REQUIRED', $this->gateway->getParameter('identifier'));
    }

    public function testSetTransactionType()
    {
        $this->gateway->setTransactionType(TransactionTypes::REFUND);

        $this->assertEquals(TransactionTypes::REFUND, $this->gateway->getParameter('transactionType'));
    }

    public function testSetTransactionTypeDoesNotThrowWhenNoPayMethodSet()
    {
        $this->gateway->setTransactionType(TransactionTypes::PREAUTHORIZATION);

        $this->assertEquals(TransactionTypes::PREAUTHORIZATION, $this->gateway->getParameter('transactionType'));
    }

    public function testPurchaseReturnsPurchaseRequest()
    {
        $request = $this->gateway->purchase([
            'amount' => '10.00',
            'currency' => 'EUR',
            'transactionId' => self::ORDER_ID,
        ]);

        $this->assertInstanceOf(PurchaseRequest::class, $request);
    }

    public function testAuthorizeReturnsAuthorizeRequest()
    {
        $request = $this->gateway->authorize([
            'amount' => '10.00',
            'currency' => 'EUR',
            'transactionId' => self::ORDER_ID,
        ]);

        $this->assertInstanceOf(AuthorizeRequest::class, $request);
    }

    public function testCompletePurchaseReturnsCompletePurchaseRequest()
    {
        $request = $this->gateway->completePurchase();

        $this->assertInstanceOf(CompletePurchaseRequest::class, $request);
    }

    public function testCompleteAuthorizeReturnsCompleteAuthorizeRequest()
    {
        $request = $this->gateway->completeAuthorize();

        $this->assertInstanceOf(CompleteAuthorizeRequest::class, $request);
    }

    public function testAcceptNotificationReturnsNotificationInterface()
    {
        $notification = $this->gateway->acceptNotification();

        $this->assertInstanceOf(AcceptNotification::class, $notification);
    }

    public function testCheckCallbackResponseReturnsBooleanByDefault()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        $request = Request::create(
            '/',
            'POST',
            $payload
        );

        $result = $this->gateway->checkCallbackResponse($request);

        $this->assertTrue($result);
    }

    public function testCheckCallbackResponseReturnsObjectWhenRequested()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        $request = Request::create(
            '/',
            'POST',
            $payload
        );

        $result = $this->gateway->checkCallbackResponse($request, true);

        $this->assertInstanceOf(CallbackResponse::class, $result);
    }

    public function testDecodeCallbackResponseFromPost()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        $request = Request::create(
            '/',
            'POST',
            $payload
        );

        $decoded = $this->gateway->decodeCallbackResponse($request);

        $this->assertIsArray($decoded);
        $this->assertEquals(self::ORDER_ID, $decoded['Ds_Order']);
        $this->assertEquals('0000', $decoded['Ds_Response']);
    }

    public function testDecodeCallbackResponseFromGet()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        $request = Request::create(
            '/',
            'GET',
            $payload
        );

        $decoded = $this->gateway->decodeCallbackResponse($request);

        $this->assertIsArray($decoded);
        $this->assertEquals(self::ORDER_ID, $decoded['Ds_Order']);
    }

    public function testDecodeCallbackResponseWithUrlSafeBase64()
    {
        $params = $this->successfulCallbackParameters();
        // Use URL-safe base64 (- and _ instead of + and /)
        $merchantParameters = strtr(base64_encode(json_encode($params)), '+/', '-_');

        $request = Request::create(
            '/',
            'POST',
            ['Ds_MerchantParameters' => $merchantParameters]
        );

        $decoded = $this->gateway->decodeCallbackResponse($request);

        $this->assertIsArray($decoded);
        $this->assertEquals(self::ORDER_ID, $decoded['Ds_Order']);
    }
}
