<?php

namespace Omnipay\Redsys\Tests;

use Omnipay\Redsys\Encryptor\Encryptor;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for Redsys tests.
 *
 * Provides helpers for generating valid signed request/response data
 * so individual tests do not need to duplicate cryptographic setup.
 */
abstract class RedsysTestCase extends TestCase
{
    /**
     * A base64-encoded 24-byte test merchant key.
     * This is the Redsys sandbox merchant key used in official documentation.
     */
    protected const MERCHANT_KEY = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';

    protected const MERCHANT_CODE = '999008881';
    protected const TERMINAL = '001';
    protected const ORDER_ID = '0001020304';

    /**
     * Build merchant parameters and a valid HMAC-SHA256 callback signature for the given data.
     *
     * @param array $decodedParameters Associative array of Redsys response parameters.
     * @return array{Ds_MerchantParameters: string, Ds_Signature: string, Ds_SignatureVersion: string}
     */
    protected function buildCallbackPayload(array $decodedParameters): array
    {
        $merchantParameters = base64_encode(json_encode($decodedParameters));
        $orderId = $decodedParameters['Ds_Order'];

        $key = base64_decode(self::MERCHANT_KEY);
        $key = Encryptor::encrypt_3DES($orderId, $key);
        $signature = strtr(base64_encode(hash_hmac('sha256', $merchantParameters, $key, true)), '+/', '-_');

        return [
            'Ds_MerchantParameters' => $merchantParameters,
            'Ds_Signature' => $signature,
            'Ds_SignatureVersion' => 'HMAC_SHA256_V1',
        ];
    }

    /**
     * Return a default set of successful callback parameters.
     */
    protected function successfulCallbackParameters(): array
    {
        return [
            'Ds_Date' => '01/12/2020',
            'Ds_Hour' => '12:10',
            'Ds_SecurePayment' => '0',
            'Ds_Amount' => '10000',
            'Ds_Currency' => '978',
            'Ds_Order' => self::ORDER_ID,
            'Ds_MerchantCode' => self::MERCHANT_CODE,
            'Ds_Terminal' => self::TERMINAL,
            'Ds_Response' => '0000',
            'Ds_TransactionType' => '0',
            'Ds_MerchantData' => '',
            'Ds_AuthorisationCode' => '999999',
            'Ds_Card_Country' => '724',
        ];
    }

    /**
     * Return a default set of failed callback parameters (response >= 100).
     */
    protected function failedCallbackParameters(): array
    {
        $params = $this->successfulCallbackParameters();
        $params['Ds_Response'] = '0101';
        return $params;
    }
}
