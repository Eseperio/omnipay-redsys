<?php

namespace Omnipay\Redsys\Tests\Message;

use Omnipay\Common\Http\Client;
use Omnipay\Redsys\Message\CompleteAuthorizeRequest;
use Omnipay\Redsys\Message\CompleteAuthorizeResponse;
use Omnipay\Redsys\Tests\RedsysTestCase;
use Symfony\Component\HttpFoundation\Request;

class CompleteAuthorizeRequestTest extends RedsysTestCase
{
    public function testSendDataReturnsCompleteAuthorizeResponse()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);

        $_POST['Ds_MerchantParameters'] = $payload['Ds_MerchantParameters'];
        $_POST['Ds_Signature'] = $payload['Ds_Signature'];

        $httpClient = new Client();
        $httpRequest = Request::create('/', 'POST', $payload);

        $request = new CompleteAuthorizeRequest($httpClient, $httpRequest);
        $request->initialize([
            'merchantKey' => self::MERCHANT_KEY,
            'merchantCode' => self::MERCHANT_CODE,
        ]);

        $response = $request->send();

        $this->assertInstanceOf(CompleteAuthorizeResponse::class, $response);
        $this->assertTrue($response->isSuccessful());

        unset($_POST['Ds_MerchantParameters'], $_POST['Ds_Signature']);
    }

    public function testExtendsCompletePurchaseRequest()
    {
        $httpClient = new Client();
        $httpRequest = Request::createFromGlobals();

        $request = new CompleteAuthorizeRequest($httpClient, $httpRequest);

        $this->assertInstanceOf(\Omnipay\Redsys\Message\CompletePurchaseRequest::class, $request);
    }
}
