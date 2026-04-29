<?php

namespace Omnipay\Redsys\Tests\Message;

use Omnipay\Redsys\Message\PurchaseRequest;
use Omnipay\Redsys\Message\CompleteAuthorizeResponse;
use Omnipay\Redsys\Tests\RedsysTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CompleteAuthorizeResponseTest extends RedsysTestCase
{
    private function buildResponse(array $decodedParameters, bool $success = true): CompleteAuthorizeResponse
    {
        /** @var PurchaseRequest|MockObject $request */
        $request = $this->createMock(PurchaseRequest::class);

        return new CompleteAuthorizeResponse($request, [
            'success' => $success,
            'decodedParameters' => $decodedParameters,
        ]);
    }

    public function testIsSuccessfulReturnsTrueWhenSuccessIsTrue()
    {
        $params = $this->successfulCallbackParameters();
        $response = $this->buildResponse($params, true);

        $this->assertTrue($response->isSuccessful());
    }

    public function testIsSuccessfulReturnsFalseWhenSuccessIsFalse()
    {
        $params = $this->failedCallbackParameters();
        $response = $this->buildResponse($params, false);

        $this->assertFalse($response->isSuccessful());
    }

    public function testExtendsCompletePurchaseResponse()
    {
        $params = $this->successfulCallbackParameters();
        $response = $this->buildResponse($params);

        $this->assertInstanceOf(\Omnipay\Redsys\Message\CompletePurchaseResponse::class, $response);
    }

    public function testGetTransactionReferenceReturnsAuthorisationCode()
    {
        $params = $this->successfulCallbackParameters();
        $response = $this->buildResponse($params);

        $this->assertEquals('999999', $response->getTransactionReference());
    }

    public function testGetTransactionIdReturnsOrder()
    {
        $params = $this->successfulCallbackParameters();
        $response = $this->buildResponse($params);

        $this->assertEquals(self::ORDER_ID, $response->getTransactionId());
    }
}
