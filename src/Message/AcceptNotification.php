<?php

namespace Omnipay\Redsys\Message;

use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Redsys\Traits\SignatureCheckerTrait;
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
    use SignatureCheckerTrait;

    /**
     * @var
     */
    private $errorMsg;
    /**
     * @var null | Request
     */
    private $requestData = null;

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

        if (!$this->checkSignature(
            $rawParameters,
            $decodedParameters['Ds_Order'],
            $this->getData('merchantKey'),
            $data['Ds_Signature']
        )) {
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
