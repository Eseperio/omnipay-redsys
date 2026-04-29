<?php

namespace Omnipay\Redsys\Tests\Message;

use Omnipay\Redsys\Exception\BadSignatureException;
use Omnipay\Redsys\Exception\CallbackException;
use Omnipay\Redsys\Message\CallbackResponse;
use Omnipay\Redsys\Tests\RedsysTestCase;
use Symfony\Component\HttpFoundation\Request;

class CallbackResponseTest extends RedsysTestCase
{
    private function buildCallbackResponse(array $payload): CallbackResponse
    {
        $request = Request::create('/', 'POST', $payload);

        return new CallbackResponse($request, self::MERCHANT_KEY);
    }

    private function buildGetCallbackResponse(array $payload): CallbackResponse
    {
        $request = Request::create('/', 'GET', $payload);

        return new CallbackResponse($request, self::MERCHANT_KEY);
    }

    public function testIsSuccessfulReturnsTrueForValidSuccessfulCallback()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        $response = $this->buildCallbackResponse($payload);

        $this->assertTrue($response->isSuccessful());
    }

    public function testIsSuccessfulReturnsTrueForGetRequest()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        $response = $this->buildGetCallbackResponse($payload);

        $this->assertTrue($response->isSuccessful());
    }

    public function testIsSuccessfulThrowsBadSignatureWhenMissingParameters()
    {
        $this->expectException(BadSignatureException::class);

        $request = Request::create('/', 'POST', []);
        $response = new CallbackResponse($request, self::MERCHANT_KEY);
        $response->isSuccessful();
    }

    public function testIsSuccessfulThrowsBadSignatureForInvalidSignature()
    {
        $this->expectException(BadSignatureException::class);

        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);
        $payload['Ds_Signature'] = 'invalid_signature_tampered';

        $response = $this->buildCallbackResponse($payload);
        $response->isSuccessful();
    }

    public function testIsSuccessfulThrowsCallbackExceptionForFailedResponse()
    {
        $this->expectException(CallbackException::class);

        $params = $this->failedCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        $response = $this->buildCallbackResponse($payload);
        $response->isSuccessful();
    }

    public function testIsSuccessfulThrowsCallbackExceptionWithCorrectCode()
    {
        try {
            $params = $this->failedCallbackParameters();
            $params['Ds_Response'] = '0101';
            $payload = $this->buildCallbackPayload($params);

            $response = $this->buildCallbackResponse($payload);
            $response->isSuccessful();

            $this->fail('Expected CallbackException was not thrown');
        } catch (CallbackException $e) {
            $this->assertEquals(101, $e->getCode());
        }
    }

    public function testIsSuccessfulAcceptsResponseCode99AsSuccess()
    {
        $params = $this->successfulCallbackParameters();
        $params['Ds_Response'] = '0099';
        $payload = $this->buildCallbackPayload($params);

        $response = $this->buildCallbackResponse($payload);

        $this->assertTrue($response->isSuccessful());
    }

    public function testIsSuccessfulThrowsCallbackExceptionForResponseCode100()
    {
        $this->expectException(CallbackException::class);

        $params = $this->successfulCallbackParameters();
        $params['Ds_Response'] = '0100';
        $payload = $this->buildCallbackPayload($params);

        $response = $this->buildCallbackResponse($payload);
        $response->isSuccessful();
    }

    public function testIsSuccessfulThrowsBadSignatureForTamperedMerchantParameters()
    {
        $this->expectException(BadSignatureException::class);

        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        // Tamper the merchant parameters
        $decoded = json_decode(base64_decode($payload['Ds_MerchantParameters']), true);
        $decoded['Ds_Amount'] = '99999';
        $payload['Ds_MerchantParameters'] = base64_encode(json_encode($decoded));

        $response = $this->buildCallbackResponse($payload);
        $response->isSuccessful();
    }
}
