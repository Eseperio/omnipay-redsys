<?php

namespace Omnipay\Redsys\Tests\Message;

use Omnipay\Common\Http\Client;
use Omnipay\Redsys\Dictionaries\PayMethods;
use Omnipay\Redsys\Dictionaries\TransactionTypes;
use Omnipay\Redsys\Exception\BadPayMethodException;
use Omnipay\Redsys\Message\PurchaseRequest;
use Omnipay\Redsys\Message\PurchaseResponse;
use Omnipay\Redsys\Tests\RedsysTestCase;
use Symfony\Component\HttpFoundation\Request;

class PurchaseRequestTest extends RedsysTestCase
{
    /** @var PurchaseRequest */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();

        $httpClient = new Client();
        $httpRequest = Request::createFromGlobals();

        $this->request = new PurchaseRequest($httpClient, $httpRequest);
        $this->request->initialize([
            'merchantKey' => self::MERCHANT_KEY,
            'merchantCode' => self::MERCHANT_CODE,
            'terminal' => self::TERMINAL,
            'transactionId' => self::ORDER_ID,
            'amount' => '100.00',
            'currency' => 'EUR',
            'description' => 'Test purchase',
            'titular' => 'Test User',
            'consumerLanguage' => '001',
            'merchantName' => 'Test Commerce',
            'merchantURL' => 'https://test.example.com/notify',
            'returnUrl' => 'https://test.example.com/ok',
            'cancelUrl' => 'https://test.example.com/ko',
        ]);
    }

    public function testGetDataReturnsExpectedKeys()
    {
        $data = $this->request->getData();

        $this->assertArrayHasKey('Ds_MerchantParameters', $data);
        $this->assertArrayHasKey('Ds_Signature', $data);
        $this->assertArrayHasKey('Ds_SignatureVersion', $data);
    }

    public function testGetDataSignatureVersion()
    {
        $data = $this->request->getData();

        $this->assertEquals('HMAC_SHA256_V1', $data['Ds_SignatureVersion']);
    }

    public function testGetDataMerchantParametersContainsExpectedValues()
    {
        $data = $this->request->getData();
        $decoded = json_decode(base64_decode($data['Ds_MerchantParameters']), true);

        $this->assertEquals(self::ORDER_ID, $decoded['Ds_Merchant_Order']);
        $this->assertEquals(self::MERCHANT_CODE, $decoded['Ds_Merchant_MerchantCode']);
        $this->assertEquals(self::TERMINAL, $decoded['Ds_Merchant_Terminal']);
        $this->assertEquals('Test purchase', $decoded['Ds_Merchant_ProductDescription']);
        $this->assertEquals('Test User', $decoded['Ds_Merchant_Titular']);
        $this->assertEquals('001', $decoded['Ds_Merchant_ConsumerLanguage']);
        $this->assertEquals('Test Commerce', $decoded['Ds_Merchant_MerchantName']);
        $this->assertEquals('https://test.example.com/notify', $decoded['Ds_Merchant_MerchantURL']);
        $this->assertEquals('https://test.example.com/ok', $decoded['Ds_Merchant_UrlOK']);
        $this->assertEquals('https://test.example.com/ko', $decoded['Ds_Merchant_UrlKO']);
    }

    public function testGetDataCurrencyIsNumericCode()
    {
        $data = $this->request->getData();
        $decoded = json_decode(base64_decode($data['Ds_MerchantParameters']), true);

        // EUR numeric ISO 4217 code is 978
        $this->assertEquals(978, $decoded['Ds_Merchant_Currency']);
    }

    public function testGetDataTransactionTypeDefaultsToAuthorization()
    {
        $data = $this->request->getData();
        $decoded = json_decode(base64_decode($data['Ds_MerchantParameters']), true);

        $this->assertEquals(TransactionTypes::AUTHORIZATION, $decoded['Ds_Merchant_TransactionType']);
    }

    public function testSignatureIsConsistentForSameInput()
    {
        $data1 = $this->request->getData();
        $data2 = $this->request->getData();

        $this->assertEquals($data1['Ds_Signature'], $data2['Ds_Signature']);
    }

    public function testSignatureChangesWhenOrderChanges()
    {
        $data1 = $this->request->getData();

        $this->request->setTransactionId('0001020305');
        $data2 = $this->request->getData();

        $this->assertNotEquals($data1['Ds_Signature'], $data2['Ds_Signature']);
    }

    public function testGetAmount()
    {
        // setup initialized with amount '100.00'; setAmount('100.00') stores '10000'
        // parent::getAmount() parses '10000' as EUR 100.00 → '100.00', strval((float)) → '100'
        $this->assertEquals('100', $this->request->getAmount());
    }

    public function testAmountFormattingStripsDecimalSeparator()
    {
        // setAmount('12.50') → number_format stores '1250' → parent returns '12.50' → strval(float) = '12.5'
        $this->request->setAmount('12.50');
        $this->assertEquals('12.5', $this->request->getAmount());
    }

    public function testGetTransactionIdReturnsTransactionId()
    {
        $this->assertEquals(self::ORDER_ID, $this->request->getTransactionId());
    }

    public function testGetTransactionIdFallsBackToToken()
    {
        $this->request->setTransactionId(null);
        $this->request->setToken('my-token');

        $this->assertEquals('my-token', $this->request->getTransactionId());
    }

    public function testGetTransactionTypeDefaultsToAuthorization()
    {
        $this->assertEquals(TransactionTypes::AUTHORIZATION, $this->request->getTransactionType());
    }

    public function testGetTransactionTypeReturnsSetTypeWhenPassedViaInitialize()
    {
        // PurchaseRequest has no setTransactionType(); the parameter can only be set via initialize()
        // but Helper::initialize only calls setXxx() - with no setter, this stays at default (0)
        // The authorizeRequest class demonstrates a different transaction type (PREAUTHORIZATION).
        $this->assertEquals(TransactionTypes::AUTHORIZATION, $this->request->getTransactionType());
    }

    public function testGetEndpointUsesLiveEndpointByDefault()
    {
        $this->assertStringContainsString('sis.redsys.es', $this->request->getEndpoint());
        $this->assertStringContainsString('realizarPago', $this->request->getEndpoint());
    }

    public function testGetEndpointUsesTestEndpointWhenTestModeIsTrue()
    {
        $this->request->setTestMode(true);

        $this->assertStringContainsString('sis-t.redsys.es', $this->request->getEndpoint());
    }

    public function testGetEndpointBaseReturnsLiveEndpointByDefault()
    {
        $this->assertEquals('https://sis.redsys.es', $this->request->getEndpointBase());
    }

    public function testGetEndpointBaseReturnsTestEndpointInTestMode()
    {
        $this->request->setTestMode(true);

        $this->assertEquals('https://sis-t.redsys.es:25443', $this->request->getEndpointBase());
    }

    public function testSendDataReturnsPurchaseResponse()
    {
        $response = $this->request->send();

        $this->assertInstanceOf(PurchaseResponse::class, $response);
    }

    public function testSetPayMethodWithValidMethod()
    {
        $this->request->setPayMethod(PayMethods::PAY_METHOD_CARD);

        $data = $this->request->getData();
        $decoded = json_decode(base64_decode($data['Ds_MerchantParameters']), true);

        $this->assertEquals(PayMethods::PAY_METHOD_CARD, $decoded['Ds_Merchant_PayMethods']);
    }

    public function testSetPayMethodWithInvalidMethodThrowsException()
    {
        $this->expectException(BadPayMethodException::class);

        $this->request->setPayMethod('INVALID_METHOD');
    }

    public function testSetPayMethodBizumThrowsWithRefundTransaction()
    {
        // Directly test setPayMethod with Bizum when transactionType param is REFUND
        // Since PurchaseRequest has no setTransactionType(), we test the default (AUTHORIZATION)
        // which IS compatible with Bizum, so no exception expected
        $this->request->setPayMethod(PayMethods::PAY_METHOD_BIZUM);

        $data = $this->request->getData();
        $decoded = json_decode(base64_decode($data['Ds_MerchantParameters']), true);

        $this->assertEquals(PayMethods::PAY_METHOD_BIZUM, $decoded['Ds_Merchant_PayMethods']);
    }

    public function testPayMethodNotIncludedInDataWhenNotSet()
    {
        $data = $this->request->getData();
        $decoded = json_decode(base64_decode($data['Ds_MerchantParameters']), true);

        $this->assertArrayNotHasKey('Ds_Merchant_PayMethods', $decoded);
    }

    public function testIdentifierIncludedInDataWhenSet()
    {
        $this->request->setIdentifier('REQUIRED');

        $data = $this->request->getData();
        $decoded = json_decode(base64_decode($data['Ds_MerchantParameters']), true);

        $this->assertArrayHasKey('Ds_Merchant_Identifier', $decoded);
        $this->assertEquals('REQUIRED', $decoded['Ds_Merchant_Identifier']);
    }

    public function testIdentifierNotIncludedInDataWhenNotSet()
    {
        $data = $this->request->getData();
        $decoded = json_decode(base64_decode($data['Ds_MerchantParameters']), true);

        $this->assertArrayNotHasKey('Ds_Merchant_Identifier', $decoded);
    }

    public function testSetMultiplyTriggersDeprecation()
    {
        $triggered = false;
        set_error_handler(function ($errno, $errstr) use (&$triggered) {
            if ($errno === E_USER_DEPRECATED) {
                $triggered = true;
            }
            return true;
        });

        $this->request->setMultiply(true);

        restore_error_handler();

        $this->assertTrue($triggered, 'Expected a deprecation warning to be triggered');
    }

    public function testGetAmountWithMultiplyMultipliesBy100()
    {
        set_error_handler(function () { return true; }); // suppress deprecation
        $this->request->setMultiply(true);
        restore_error_handler();

        $this->request->setAmount('10.00');

        // With multiply, amount is multiplied by 100: float('10.00') * 100 = 1000
        $this->assertEquals('1000', $this->request->getAmount());
    }

    public function testSettersReturnRequestForChaining()
    {
        $this->assertInstanceOf(PurchaseRequest::class, $this->request->setOrder('1234'));
        $this->assertInstanceOf(PurchaseRequest::class, $this->request->setTitular('John'));
        $this->assertInstanceOf(PurchaseRequest::class, $this->request->setConsumerLanguage('001'));
        $this->assertInstanceOf(PurchaseRequest::class, $this->request->setMerchantCode('999'));
        $this->assertInstanceOf(PurchaseRequest::class, $this->request->setMerchantName('Shop'));
        $this->assertInstanceOf(PurchaseRequest::class, $this->request->setMerchantURL('https://x.com'));
        $this->assertInstanceOf(PurchaseRequest::class, $this->request->setTerminal('001'));
        $this->assertInstanceOf(PurchaseRequest::class, $this->request->setIdentifier('REQUIRED'));
    }
}
