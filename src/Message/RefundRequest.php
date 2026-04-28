<?php

namespace Omnipay\Redsys\Message;

use Omnipay\Redsys\Dictionaries\TransactionTypes;
use Omnipay\Redsys\Traits\SignatureCheckerTrait;

/**
 * Redsys Refund Request
 *
 * @author Daniel Subiabre https://github.com/subiabre
 */
class RefundRequest extends PurchaseRequest
{
    public function getTransactionType()
    {
        return TransactionTypes::REFUND;
    }

    public function getEndpoint()
    {
        return $this->getEndpointBase() . '/sis/rest/trataPeticionREST';
    }

    public function getData()
    {
        $data = [];

        $data['Ds_Merchant_Order'] = $this->getTransactionId();
        $data['Ds_Merchant_Terminal'] = $this->getParameter('terminal');
        $data['Ds_Merchant_MerchantCode'] = $this->getParameter('merchantCode');
        $data['Ds_Merchant_Currency'] = $this->getCurrencyRedsys();
        $data['Ds_Merchant_TransactionType'] = $this->getTransactionType();
        $data['Ds_Merchant_Amount'] = $this->getAmount();

        $merchantParameters = base64_encode(json_encode($data));

        return [
            'Ds_MerchantParameters' => $merchantParameters,
            'Ds_Signature' => $this->generateSignature($merchantParameters),
            'Ds_SignatureVersion' => 'HMAC_SHA256_V1'
        ];
    }

    public function sendData($data)
    {
        $response = $this->httpClient->request(
            'POST',
            $this->getEndpoint(),
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );

        return $this->response = new RefundResponse($this, [
            'merchantKey' => $this->getParameter('merchantKey'),
            'responseData' => \json_decode($response->getBody()->getContents())
        ]);
    }
}
