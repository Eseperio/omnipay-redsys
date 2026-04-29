<?php

namespace Omnipay\Redsys\Tests\Message;

use Omnipay\Redsys\Message\PurchaseRequest;
use Omnipay\Redsys\Message\CompletePurchaseResponse;
use Omnipay\Redsys\Tests\RedsysTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CompletePurchaseResponseTest extends RedsysTestCase
{
    private function buildResponse(array $decodedParameters, bool $success = true): CompletePurchaseResponse
    {
        /** @var PurchaseRequest|MockObject $request */
        $request = $this->createMock(PurchaseRequest::class);

        return new CompletePurchaseResponse($request, [
            'success' => $success,
            'decodedParameters' => $decodedParameters,
        ]);
    }

    private function makeParams(string $responseCode = '0000', string $authCode = '999999'): array
    {
        return array_merge($this->successfulCallbackParameters(), [
            'Ds_Response' => $responseCode,
            'Ds_AuthorisationCode' => $authCode,
        ]);
    }

    public function testIsSuccessfulReturnsTrueWhenSuccessIsTrue()
    {
        $response = $this->buildResponse($this->makeParams(), true);

        $this->assertTrue($response->isSuccessful());
    }

    public function testIsSuccessfulReturnsFalseWhenSuccessIsFalse()
    {
        $response = $this->buildResponse($this->makeParams('0101'), false);

        $this->assertFalse($response->isSuccessful());
    }

    public function testGetCodeReturnsResponseCode()
    {
        $response = $this->buildResponse($this->makeParams('0000'));

        $this->assertEquals('0000', $response->getCode());
    }

    public function testGetTransactionReferenceReturnsAuthorisationCode()
    {
        $response = $this->buildResponse($this->makeParams('0000', 'AUTH123'));

        $this->assertEquals('AUTH123', $response->getTransactionReference());
    }

    public function testGetTransactionIdReturnsOrder()
    {
        $response = $this->buildResponse($this->makeParams());

        $this->assertEquals(self::ORDER_ID, $response->getTransactionId());
    }

    public function testGetMessageReturnsStringForKnownCode()
    {
        $response = $this->buildResponse($this->makeParams('0000'));

        // getMessage should return a non-empty string
        $this->assertIsString($response->getMessage());
        $this->assertNotEmpty($response->getMessage());
    }

    public function testGetMessageForUnknownCodeReturnsFallback()
    {
        // Use a code that almost certainly won't be in the messages library
        $response = $this->buildResponse($this->makeParams('9999'));

        $message = $response->getMessage();
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
    }
}
