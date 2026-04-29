<?php

namespace Omnipay\Redsys\Tests\Message;

use Omnipay\Common\Http\Client;
use Omnipay\Redsys\Dictionaries\TransactionTypes;
use Omnipay\Redsys\Message\AuthorizeRequest;
use Omnipay\Redsys\Tests\RedsysTestCase;
use Symfony\Component\HttpFoundation\Request;

class AuthorizeRequestTest extends RedsysTestCase
{
    /** @var AuthorizeRequest */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();

        $httpClient = new Client();
        $httpRequest = Request::createFromGlobals();

        $this->request = new AuthorizeRequest($httpClient, $httpRequest);
        $this->request->initialize([
            'merchantKey' => self::MERCHANT_KEY,
            'merchantCode' => self::MERCHANT_CODE,
            'terminal' => self::TERMINAL,
            'transactionId' => self::ORDER_ID,
            'amount' => '100.00',
            'currency' => 'EUR',
            'description' => 'Test authorize',
            'returnUrl' => 'https://test.example.com/ok',
            'cancelUrl' => 'https://test.example.com/ko',
        ]);
    }

    public function testGetTransactionTypeReturnsPreauthorization()
    {
        $this->assertEquals(TransactionTypes::PREAUTHORIZATION, $this->request->getTransactionType());
    }

    public function testTransactionTypeInDataIsPreauthorization()
    {
        $data = $this->request->getData();
        $decoded = json_decode(base64_decode($data['Ds_MerchantParameters']), true);

        $this->assertEquals(TransactionTypes::PREAUTHORIZATION, $decoded['Ds_Merchant_TransactionType']);
    }

    public function testGetDataReturnsExpectedKeys()
    {
        $data = $this->request->getData();

        $this->assertArrayHasKey('Ds_MerchantParameters', $data);
        $this->assertArrayHasKey('Ds_Signature', $data);
        $this->assertArrayHasKey('Ds_SignatureVersion', $data);
    }

    public function testExtendsFromPurchaseRequest()
    {
        $this->assertInstanceOf(\Omnipay\Redsys\Message\PurchaseRequest::class, $this->request);
    }
}
