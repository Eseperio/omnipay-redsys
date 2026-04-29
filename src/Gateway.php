<?php

namespace Omnipay\Redsys;

use Omnipay\Common\AbstractGateway;
use Omnipay\Redsys\Dictionaries\PayMethods;
use Omnipay\Redsys\Dictionaries\TransactionTypes;
use Omnipay\Redsys\Exception\BadPayMethodException;
use Omnipay\Redsys\Message\CallbackResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Redsys Gateway
 *
 * @author Javier Sampedro <jsampedro77@gmail.com>
 * @author NitsNets Studio <github@nitsnets.com>
 */
class Gateway extends AbstractGateway
{


    /**
     * @return array
     */
    public function getDefaultParameters()
    {
        return [
            'titular' => '',
            'consumerLanguage' => '001',
            'currency' => 'EUR',
            'terminal' => '001',
            'merchantURL' => '',
            'merchantName' => '',
            'transactionType' => TransactionTypes::AUTHORIZATION,
            'signatureMode' => 'simple',
            'testMode' => false,
            'payMethods' => PayMethods::PAY_METHOD_CARD,
        ];
    }

    /**
     * @param $merchantName
     */
    public function setMerchantName($merchantName)
    {
        $this->setParameter('merchantName', $merchantName);
    }


    /**
     * @param $transactionType
     * @return void
     * @throws \Omnipay\Redsys\Exception\BadPayMethodException
     */
    public function setTransactionType($transactionType)
    {
        $payMethod = $this->getParameter('merchantPaymethod');
        if ($payMethod) {
            $supported = PayMethods::supportedOperationTypes();
            if (is_array($supported[$payMethod]) && !in_array($transactionType, $supported[$payMethod])) {
                throw new BadPayMethodException($payMethod, $transactionType);
            }
        }
        $this->setParameter('transactionType', $transactionType);
    }

    /**
     * @param $merchantKey
     */
    public function setMerchantKey($merchantKey)
    {
        $this->setParameter('merchantKey', $merchantKey);
    }

    /**
     * @param $merchantCode
     */
    public function setMerchantCode($merchantCode)
    {
        $this->setParameter('merchantCode', $merchantCode);
    }

    /**
     * @param $merchantURL
     * @see \Omnipay\Redsys\Message\PurchaseRequest::setMerchantURL()
     * @deprecated merchantName must be defined in paymentRequest, not in gateway
     */
    public function setMerchantURL($merchantURL)
    {
        $this->setParameter('merchantURL', $merchantURL);
    }

    /**
     * @param $terminal
     */
    public function setTerminal($terminal)
    {
        $this->setParameter('terminal', $terminal);
    }

    /**
     * @param $signatureMode
     */
    public function setSignatureMode($signatureMode)
    {
        $this->setParameter('signatureMode', $signatureMode);
    }

    /**
     * @param $consumerLanguage
     * @deprecated this must be defined in paymentRequest, not in gateway. Only affects to purchase requests
     * @see PurchaseRequest::setConsumerLanguage()
     */
    public function setConsumerLanguage($consumerLanguage)
    {
        $this->setParameter('consumerLanguage', $consumerLanguage);
    }

    /**
     * @param $returnUrl
     * @deprecated returnUrl must be defined in paymentRequest, not in gateway
     * @see \Omnipay\Redsys\Message\PurchaseRequest::setReturnUrl()
     */
    public function setReturnUrl($returnUrl)
    {
        $this->setParameter('returnUrl', $returnUrl);
    }

    /**
     * @param $cancelUrl
     * @see \Omnipay\Redsys\Message\PurchaseRequest::setCancelUrl()
     * @deprecated returnUrl must be defined in paymentRequest, not in gateway
     */
    public function setCancelUrl($cancelUrl)
    {
        $this->setParameter('cancelUrl', $cancelUrl);
    }

    /**
     * @param $currency
     */
    public function setCurrencyMerchant($currency)
    {
        $this->setParameter('merchantCurrency', $currency);
    }

    /**
     * Sets the identifier parameter. This parameter is used to flag in our request that we want a token back or to
     * send our token.
     *
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->setParameter('identifier', $identifier);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Redsys';
    }

    /**
     * @param array $parameters
     * @return \Omnipay\Redsys\Message\AbstractRequest|\Omnipay\Common\Message\RequestInterface
     */
    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Redsys\Message\AuthorizeRequest', $parameters);
    }

    /**
     * @param array $parameters
     * @return \Omnipay\Redsys\Message\AbstractRequest|\Omnipay\Common\Message\RequestInterface
     */
    public function completeAuthorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Redsys\Message\CompleteAuthorizeRequest', $parameters);
    }

    /**
     * @param array $parameters
     * @return \Omnipay\Redsys\Message\PurchaseRequest
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Redsys\Message\PurchaseRequest', $parameters);
    }

    /**
     * @param array $parameters
     * @return \Omnipay\Common\Message\AbstractRequest|\Omnipay\Common\Message\RequestInterface
     */
    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Redsys\Message\CompletePurchaseRequest', $parameters);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param bool $returnObject
     * @return bool|CallbackResponse
     * @throws Exception\BadSignatureException
     * @throws Exception\CallbackException
     */
    public function checkCallbackResponse(Request $request, $returnObject = false)
    {
        $response = new CallbackResponse($request, $this->getParameter('merchantKey'));

        if ($returnObject) {
            return $response;
        }

        return $response->isSuccessful();
    }

    /**
     * @param array $options
     * @return \Omnipay\Common\Message\NotificationInterface
     */
    public function acceptNotification(array $options = array()): \Omnipay\Common\Message\NotificationInterface
    {
        return new \Omnipay\Redsys\Message\AcceptNotification($this->getParameter('merchantKey') ?? '');
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function decodeCallbackResponse(Request $request)
    {
        return json_decode(base64_decode(strtr($request->request->get('Ds_MerchantParameters') ?? $request->query->get('Ds_MerchantParameters') ?? '', '-_', '+/')), true);
    }
}
