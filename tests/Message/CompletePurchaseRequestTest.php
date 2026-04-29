<?php

namespace Omnipay\Redsys\Tests\Message;

use Omnipay\Common\Http\Client;
use Omnipay\Redsys\Exception\BadSignatureException;
use Omnipay\Redsys\Message\CompletePurchaseRequest;
use Omnipay\Redsys\Message\CompletePurchaseResponse;
use Omnipay\Redsys\Tests\RedsysTestCase;
use Symfony\Component\HttpFoundation\Request;

class CompletePurchaseRequestTest extends RedsysTestCase
{
    private function buildRequest(array $postData = []): CompletePurchaseRequest
    {
        $httpClient = new Client();
        $httpRequest = Request::create('/', 'POST', $postData);

        $request = new CompletePurchaseRequest($httpClient, $httpRequest);
        $request->initialize([
            'merchantKey' => self::MERCHANT_KEY,
            'merchantCode' => self::MERCHANT_CODE,
        ]);

        return $request;
    }

    public function testGetDataReturnsSuccessForValidSignature()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        // CompletePurchaseRequest reads from globals, so we mock $_POST
        $_POST['Ds_MerchantParameters'] = $payload['Ds_MerchantParameters'];
        $_POST['Ds_Signature'] = $payload['Ds_Signature'];

        $request = $this->buildRequest();

        $data = $request->getData();

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('decodedParameters', $data);
        $this->assertTrue($data['success']);

        // Clean up
        unset($_POST['Ds_MerchantParameters'], $_POST['Ds_Signature']);
    }

    public function testGetDataReturnsFalseForFailedResponse()
    {
        $params = $this->failedCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        $_POST['Ds_MerchantParameters'] = $payload['Ds_MerchantParameters'];
        $_POST['Ds_Signature'] = $payload['Ds_Signature'];

        $request = $this->buildRequest();

        $data = $request->getData();

        $this->assertFalse($data['success']);

        unset($_POST['Ds_MerchantParameters'], $_POST['Ds_Signature']);
    }

    public function testGetDataThrowsBadSignatureExceptionWhenParametersMissing()
    {
        $this->expectException(BadSignatureException::class);

        $request = $this->buildRequest();
        $request->getData();
    }

    public function testGetDataThrowsBadSignatureExceptionForInvalidSignature()
    {
        $this->expectException(BadSignatureException::class);

        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        $_POST['Ds_MerchantParameters'] = $payload['Ds_MerchantParameters'];
        $_POST['Ds_Signature'] = 'invalid_signature_value';

        $request = $this->buildRequest();

        try {
            $request->getData();
        } finally {
            unset($_POST['Ds_MerchantParameters'], $_POST['Ds_Signature']);
        }
    }

    public function testSendDataReturnsCompletePurchaseResponse()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        $_POST['Ds_MerchantParameters'] = $payload['Ds_MerchantParameters'];
        $_POST['Ds_Signature'] = $payload['Ds_Signature'];

        $request = $this->buildRequest();
        $response = $request->send();

        $this->assertInstanceOf(CompletePurchaseResponse::class, $response);
        $this->assertTrue($response->isSuccessful());

        unset($_POST['Ds_MerchantParameters'], $_POST['Ds_Signature']);
    }
}
