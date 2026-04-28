<?php

namespace Omnipay\Redsys\Traits;

use Omnipay\Redsys\Encryptor\Encryptor;

trait SignatureCheckerTrait
{
    /**
     * @param string $data Base 64 encoded **Ds_MerchantParameters**
     * @param string $orderId Usually **Ds_Merchant_Order** or **Ds_Order**
     * @param string $merchantKey
     * @param string $expectedSignature
     * @return bool
     */
    private function checkSignature($data, $orderId, $merchantKey, $expectedSignature)
    {
        $key = Encryptor::encrypt_3DES($orderId, base64_decode($merchantKey));

        return strtr(base64_encode(hash_hmac('sha256', $data, $key, true)), '+/', '-_') == $expectedSignature;
    }
}
