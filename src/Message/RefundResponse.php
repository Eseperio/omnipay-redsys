<?php

namespace Omnipay\Redsys\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Redsys\Exception\BadSignatureException;
use Omnipay\Redsys\Traits\SignatureCheckerTrait;

/**
 * Redsys Refund Response
 */
class RefundResponse extends AbstractResponse
{
    use SignatureCheckerTrait;

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        $data = $this->data['responseData'];

        if (isset($data->errorCode)) {
            if (in_array($data->errorCode, ['SIS0042', 'SIS0042'])) {
                throw new BadSignatureException();
            }

            throw new InvalidResponseException($data);
        }

        $rawParameters = $data->Ds_MerchantParameters;
        $decodedParameters = json_decode(base64_decode(strtr($rawParameters, '-_', '+/')), true);

        if (!$this->checkSignature(
            $rawParameters,
            $decodedParameters['Ds_Order'],
            $this->data['merchantKey'],
            $data->Ds_Signature
        )) {
            throw new BadSignatureException();
        }

        return true;
    }
}
