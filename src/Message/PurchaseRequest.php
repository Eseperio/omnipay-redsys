<?php

namespace Omnipay\Redsys\Message;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Redsys\Dictionaries\PayMethods;
use Omnipay\Redsys\Dictionaries\TransactionTypes;
use Omnipay\Redsys\Encryptor\Encryptor;
use Omnipay\Redsys\Exception\BadPayMethodException;

/**
 * Redsys Purchase Request
 *
 * @author E. Alamo https://github.com/Eseperio
 * @author Javier Sampedro <jsampedro77@gmail.com>
 * @author NitsNets Studio <github@nitsnets.com>
 */
class PurchaseRequest extends AbstractRequest
{
    protected $liveEndpoint = 'https://sis.redsys.es';
    protected $testEndpoint = 'https://sis-t.redsys.es:25443';

    /**
     * @param $order
     * @return PurchaseRequest
     */
    public function setOrder($order)
    {
        return $this->setParameter('order', $order);
    }

    /**
     * @param $merchantPaymethod int a value from PayMethods
     * @return \Omnipay\Redsys\Message\PurchaseRequest
     * @throws \Omnipay\Redsys\Exception\BadPayMethodException
     * @see PayMethods
     */
    public function setPayMethod($merchantPaymethod)
    {
        $supported = PayMethods::supportedOperationTypes();
        if (!isset($supported[$merchantPaymethod])) {
            throw new BadPayMethodException($merchantPaymethod);
        }
        $transType = $this->getParameter('transactionType');

        if (is_array($supported[$merchantPaymethod]) && !in_array($transType, $supported[$merchantPaymethod])) {
            throw new BadPayMethodException();
        }
        return $this->setParameter('paymentMethods', $merchantPaymethod);
    }

    /**
     * @param $titular
     * @return PurchaseRequest
     */
    public function setTitular($titular)
    {
        return $this->setParameter('titular', $titular);
    }

    /**
     * @param $consumerLanguage
     * @return PurchaseRequest
     */
    public function setConsumerLanguage($consumerLanguage)
    {
        return $this->setParameter('consumerLanguage', $consumerLanguage);
    }

    /**
     * @param $merchantCode
     * @return PurchaseRequest
     */
    public function setMerchantCode($merchantCode)
    {
        return $this->setParameter('merchantCode', $merchantCode);
    }

    /**
     * @param $merchantName
     * @return PurchaseRequest
     */
    public function setMerchantName($merchantName)
    {
        return $this->setParameter('merchantName', $merchantName);
    }

    /**
     * @param $merchantURL
     * @return PurchaseRequest
     */
    public function setMerchantURL($merchantURL)
    {
        return $this->setParameter('merchantURL', $merchantURL);
    }

    /**
     * @param $merchantKey
     */
    public function setMerchantKey($merchantKey)
    {
        $this->setParameter('merchantKey', $merchantKey);
    }

    /**
     * @param $terminal
     * @return PurchaseRequest
     */
    public function setTerminal($terminal)
    {
        return $this->setParameter('terminal', $terminal);
    }

    /**
     * Sets the identifier on the purchase request.
     *
     * @param string $identifier Identifier to be set on the purchase request
     *
     * @return object
     */
    public function setIdentifier($identifier)
    {
        return $this->setParameter('identifier', $identifier);
    }

    /**
     * Gets the identifier parameter setup on the purchase request.
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->getParameter('identifier');
    }

    /**
     * @param $signatureMode
     */
    public function setSignatureMode($signatureMode)
    {
        $this->setParameter('signatureMode', $signatureMode);
    }

    /**
     * @param $multiply
     * @return PurchaseRequest
     * @deprecated
     */
    public function setMultiply($multiply)
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
        return $this->setParameter('multiply', $multiply);
    }


    /**
     * @return array|mixed
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData()
    {
        $data = [];

        $data['Ds_Merchant_Amount'] = $this->getAmount();
        $data['Ds_Merchant_Currency'] = $this->getCurrencyRedsys();
        $data['Ds_Merchant_Order'] = $this->getTransactionId();
        $data['Ds_Merchant_ProductDescription'] = $this->getDescription();

        $data['Ds_Merchant_Titular'] = $this->getParameter('titular');
        $data['Ds_Merchant_ConsumerLanguage'] = $this->getParameter('consumerLanguage');
        $data['Ds_Merchant_MerchantCode'] = $this->getParameter('merchantCode');
        $data['Ds_Merchant_MerchantName'] = $this->getParameter('merchantName');
        $data['Ds_Merchant_MerchantURL'] = $this->getParameter('merchantURL');
        $data['Ds_Merchant_Terminal'] = $this->getParameter('terminal');
        $data['Ds_Merchant_TransactionType'] = $this->getTransactionType();

        $data['Ds_Merchant_UrlOK'] = $this->getReturnUrl();
        $data['Ds_Merchant_UrlKO'] = $this->getCancelUrl();

        if (!empty($this->getParameter('identifier'))) {
            $data['Ds_Merchant_Identifier'] = $this->getParameter('identifier');
        }
        if (!empty($this->getParameter('paymentMethods'))) {
            $data['Ds_Merchant_PayMethods'] = $this->getParameter('paymentMethods');
        }

        $merchantParameters = base64_encode(json_encode($data));

        return [
            'Ds_MerchantParameters' => $merchantParameters,
            'Ds_Signature' => $this->generateSignature($merchantParameters),
            'Ds_SignatureVersion' => 'HMAC_SHA256_V1'
        ];
    }

    /**
     * @return float|int|string
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getAmount()
    {

        if ($this->getParameter('multiply')) {
            return strval((float)parent::getAmount() * 100);
        }
        return strval((float)parent::getAmount());
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        if (!empty(parent::getTransactionId())) {
            return parent::getTransactionId();
        }

        return parent::getToken();
    }

    /**
     * @return string
     */
    public function getTransactionType()
    {
        $transactionType = $this->getParameter('transactionType');
        if (empty($transactionType)) {
            $transactionType = TransactionTypes::AUTHORIZATION;
        }
        return $transactionType;
    }

    /**
     * @param $merchantParameters
     * @return string
     */
    protected function generateSignature($merchantParameters)
    {
        $key = base64_decode($this->getParameter('merchantKey'));
        $key = Encryptor::encrypt_3DES($this->getTransactionId(), $key);
        $res = hash_hmac('sha256', $merchantParameters, $key, true);

        return base64_encode($res);
    }

    /**
     * @param mixed $data
     * @return \Omnipay\Common\Message\ResponseInterface|PurchaseResponse
     */
    public function sendData($data)
    {
        return $this->response = new PurchaseResponse($this, $data);
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->getEndpointBase() . '/sis/realizarPago';
    }

    /**
     * @return string
     */
    public function getEndpointBase()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }

    /**
     * @return int
     */
    protected function getCurrencyRedsys()
    {
        $currencies = new ISOCurrencies();

        return $currencies->numericCodeFor(new Currency($this->getCurrency()));
    }
}
