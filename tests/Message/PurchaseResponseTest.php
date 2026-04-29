<?php

namespace Omnipay\Redsys\Tests\Message;

use Omnipay\Redsys\Message\PurchaseRequest;
use Omnipay\Redsys\Message\PurchaseResponse;
use Omnipay\Redsys\Tests\RedsysTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PurchaseResponseTest extends RedsysTestCase
{
    private function buildResponse(array $data = []): PurchaseResponse
    {
        /** @var PurchaseRequest|MockObject $request */
        $request = $this->createMock(PurchaseRequest::class);
        $request->method('getEndpoint')->willReturn('https://sis.redsys.es/sis/realizarPago');

        return new PurchaseResponse($request, $data);
    }

    public function testIsSuccessfulReturnsFalse()
    {
        $response = $this->buildResponse();

        $this->assertFalse($response->isSuccessful());
    }

    public function testIsRedirectReturnsTrue()
    {
        $response = $this->buildResponse();

        $this->assertTrue($response->isRedirect());
    }

    public function testGetRedirectMethodReturnsPost()
    {
        $response = $this->buildResponse();

        $this->assertEquals('POST', $response->getRedirectMethod());
    }

    public function testGetRedirectUrlReturnsEndpoint()
    {
        $response = $this->buildResponse();

        $this->assertEquals('https://sis.redsys.es/sis/realizarPago', $response->getRedirectUrl());
    }

    public function testGetRedirectDataReturnsData()
    {
        $data = [
            'Ds_MerchantParameters' => 'abc123',
            'Ds_Signature' => 'sig123',
            'Ds_SignatureVersion' => 'HMAC_SHA256_V1',
        ];

        $response = $this->buildResponse($data);

        $this->assertEquals($data, $response->getRedirectData());
    }
}
