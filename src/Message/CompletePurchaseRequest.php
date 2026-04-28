<?php

namespace Omnipay\Redsys\Message;

use Symfony\Component\HttpFoundation\Request;
use Omnipay\Redsys\Exception\BadSignatureException;
use Omnipay\Redsys\Traits\SignatureCheckerTrait;

/**
 * Redsys Complete Purchase Request
 * @deprecated since 1.3.0 Use acceptNotification instead, which is more accurate
 */
class CompletePurchaseRequest extends PurchaseRequest
{
    use SignatureCheckerTrait;

    /**
     * @return array
     * @throws BadSignatureException
     */
    public function getData()
    {
        $request = Request::createFromGlobals();

        $rawParameters = $request->request->get('Ds_MerchantParameters') ?? $request->query->get('Ds_MerchantParameters');

        if ($rawParameters === null) {
            throw new BadSignatureException();
        }

        $decodedParameters = json_decode(base64_decode(strtr($rawParameters, '-_', '+/')), true);

        if (!$this->checkSignature(
            $rawParameters,
            $decodedParameters['Ds_Order'],
            $this->getParameter('merchantKey'),
            $request->request->get('Ds_Signature') ?? $request->query->get('Ds_Signature')
        )
        ) {
            throw new BadSignatureException();
        }

        //check response, code "000" to "099" means success
        if ((int)$decodedParameters['Ds_Response'] <= 99) {
            $success = true;
        } else {
            $success = false;
        }

        return [
            'success' => $success,
            'decodedParameters' => $decodedParameters
        ];
    }

    /**
     * @param $data
     * @return CompletePurchaseResponse|PurchaseResponse
     */
    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
