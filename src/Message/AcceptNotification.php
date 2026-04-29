<?php

namespace Omnipay\Redsys\Message;

use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Redsys\Encryptor\Encryptor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Example of use:
 * ```php
 * $gateway = Omnipay::create('Redsys');
 * $gateway->initialize([
 *    'merchantId' => '123456789',
 *   'merchantKey' => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
 *  'testMode' => true
 * ]);
 * $notification = $gateway->acceptNotification();
 * if ($notification->getTransactionStatus() === NotificationInterface::STATUS_COMPLETED) {
 *    // Payment was successful
 *   $transactionReference = $notification->getTransactionReference();
 *  // Do your stuff here
 * } else {
 *   // Payment failed
 * $message = $notification->getMessage();
 * // Do your stuff here
 * }
 * ```
 */
class AcceptNotification implements NotificationInterface
{
    /**
     * @var string|null
     */
    private $errorMsg;
    /**
     * @var Request|null
     */
    private $request = null;
    /**
     * @var array|null
     */
    private $requestData = null;
    /**
     * @var string
     */
    private $merchantKey;

    /**
     * @param string $merchantKey Base64-encoded merchant key used to verify the callback signature.
     */
    public function __construct(string $merchantKey = '')
    {
        $this->merchantKey = $merchantKey;
    }

    /**
     * @return array
     * @throws \Omnipay\Redsys\Exception\BadSignatureException
     */
    public function getData()
    {
        if (empty($this->request)) {
            $this->request = Request::createFromGlobals();
            $this->requestData = $this->request->request->all();
        }
        return $this->requestData;
    }

    /**
     * @param $data
     * @param $orderId
     * @param $expectedSignature
     * @return bool
     */
    private function checkSignature($data, $orderId, $expectedSignature)
    {
        $key = Encryptor::encrypt_3DES($orderId, base64_decode($this->merchantKey));

        return strtr(base64_encode(hash_hmac('sha256', $data, $key, true)), '+/', '-_') == $expectedSignature;
    }

    /**
     * @return mixed|string
     * @throws \Omnipay\Redsys\Exception\BadSignatureException
     */
    public function getTransactionReference()
    {
        return $this->getData()['Ds_Order'] ?? '';
    }

    /**
     * @return string
     * @throws \Omnipay\Redsys\Exception\BadSignatureException
     */
    public function getTransactionStatus()
    {
        $data = $this->getData();
        $rawParameters = $data['Ds_MerchantParameters'] ?? [];
        $decodedParameters = json_decode(base64_decode(strtr($rawParameters, '-_', '+/')), true);

        if (!$this->checkSignature($rawParameters, $decodedParameters['Ds_Order'], $data['Ds_Signature'])) {
            $this->errorMsg = 'Bad signature';
            return NotificationInterface::STATUS_FAILED;
        } else {
            if ((int)$decodedParameters['Ds_Response'] <= 99) {
                return NotificationInterface::STATUS_COMPLETED;
            } else {
                $this->errorMsg = 'Transaction failed with code: ' . htmlentities($decodedParameters['Ds_Response'] ?? "");
                return NotificationInterface::STATUS_FAILED;
            }
        }
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->errorMsg;
    }
}
