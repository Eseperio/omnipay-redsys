<?php

namespace Omnipay\Redsys\Tests\Message;

use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Redsys\Message\AcceptNotification;
use Omnipay\Redsys\Tests\RedsysTestCase;

class AcceptNotificationTest extends RedsysTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up $_POST entries that may have been set during tests
        unset($_POST['Ds_MerchantParameters'], $_POST['Ds_Signature'], $_POST['Ds_SignatureVersion']);
    }

    private function setPostData(array $payload): void
    {
        $_POST['Ds_MerchantParameters'] = $payload['Ds_MerchantParameters'];
        $_POST['Ds_Signature'] = $payload['Ds_Signature'];
        $_POST['Ds_SignatureVersion'] = $payload['Ds_SignatureVersion'] ?? 'HMAC_SHA256_V1';
    }

    private function makeNotification(): AcceptNotification
    {
        return new AcceptNotification(self::MERCHANT_KEY);
    }

    public function testGetTransactionStatusReturnsCompletedForValidSuccessfulCallback()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);
        $this->setPostData($payload);

        $notification = $this->makeNotification();

        $this->assertEquals(NotificationInterface::STATUS_COMPLETED, $notification->getTransactionStatus());
    }

    public function testGetTransactionStatusReturnsFailedForInvalidSignature()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);
        $payload['Ds_Signature'] = 'bad_signature_value';
        $this->setPostData($payload);

        $notification = $this->makeNotification();

        $this->assertEquals(NotificationInterface::STATUS_FAILED, $notification->getTransactionStatus());
    }

    public function testGetTransactionStatusReturnsFailedForErrorResponseCode()
    {
        $params = $this->failedCallbackParameters();
        $payload = $this->buildCallbackPayload($params);
        $this->setPostData($payload);

        $notification = $this->makeNotification();

        $this->assertEquals(NotificationInterface::STATUS_FAILED, $notification->getTransactionStatus());
    }

    public function testGetMessageReturnsNullWhenNoError()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);
        $this->setPostData($payload);

        $notification = $this->makeNotification();
        $notification->getTransactionStatus();

        $this->assertNull($notification->getMessage());
    }

    public function testGetMessageReturnsErrorMessageAfterFailedSignature()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);
        $payload['Ds_Signature'] = 'bad_sig';
        $this->setPostData($payload);

        $notification = $this->makeNotification();
        $notification->getTransactionStatus();

        $this->assertStringContainsString('signature', strtolower($notification->getMessage()));
    }

    public function testGetMessageReturnsErrorMessageAfterFailedTransactionCode()
    {
        $params = $this->failedCallbackParameters();
        $payload = $this->buildCallbackPayload($params);
        $this->setPostData($payload);

        $notification = $this->makeNotification();
        $notification->getTransactionStatus();

        $this->assertIsString($notification->getMessage());
        $this->assertNotEmpty($notification->getMessage());
    }

    public function testGetDataReturnsPostData()
    {
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);
        $this->setPostData($payload);

        $notification = $this->makeNotification();
        $data = $notification->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('Ds_MerchantParameters', $data);
        $this->assertArrayHasKey('Ds_Signature', $data);
    }

    public function testGetTransactionReferenceReturnsEmptyWhenDataIsEncoded()
    {
        // getData() returns raw POST data with 'Ds_MerchantParameters' (encoded)
        // 'Ds_Order' is inside the encoded JSON, so getTransactionReference() returns ''
        $params = $this->successfulCallbackParameters();
        $payload = $this->buildCallbackPayload($params);
        $this->setPostData($payload);

        $notification = $this->makeNotification();

        // Ds_Order is not directly in the raw POST data; it's inside Ds_MerchantParameters
        $this->assertEquals('', $notification->getTransactionReference());
    }

    public function testGetTransactionReferenceReturnsEmptyWhenNoData()
    {
        // With no POST data, getTransactionReference() returns ''
        $notification = $this->makeNotification();

        $this->assertEquals('', $notification->getTransactionReference());
    }
}
