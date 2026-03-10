<?php

namespace Omnipay\Redsys\Message;

use Omnipay\Redsys\Encryptor\Encryptor;
use Symfony\Component\HttpFoundation\Request;
use Omnipay\Redsys\Exception\BadSignatureException;
use Omnipay\Redsys\Exception\CallbackException;

/**
 * Redsys  Callback Response
 */
class CallbackResponse
{
    private $request;
    private $merchantKey;
    private $error;

    /**
     * CallbackResponse constructor.
     * @param Request $request
     * @param $merchantKey
     */
    public function __construct(Request $request, $merchantKey)
    {
        $this->request = $request;
        $this->merchantKey = $merchantKey;
        $this->error = '';
    }

    /**
     * Check callback response from tpv
     *
     * @return boolean
     * @throws BadSignatureException
     * @throws CallbackException
     */
    public function isSuccessful()
    {
        $rawParameters = $this->request->request->get('Ds_MerchantParameters') ?? $this->request->query->get('Ds_MerchantParameters');

        if ($rawParameters === null) {
            throw new BadSignatureException();
        }

        $decodedParameters = json_decode(base64_decode(strtr($rawParameters, '-_', '+/')), true);

        if (!$this->checkSignature(
            $rawParameters,
            $decodedParameters['Ds_Order'],
            $this->request->request->get('Ds_Signature') ?? $this->request->query->get('Ds_Signature')
        )
        ) {
            throw new BadSignatureException();
        }

        //check response, code "000" to "099" means success
        if ((int)$decodedParameters['Ds_Response'] > 99) {
            throw new CallbackException(null, (int)$decodedParameters['Ds_Response']);
        }

        return true;
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
}
