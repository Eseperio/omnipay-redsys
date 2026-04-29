<?php

namespace Omnipay\Redsys\Tests\Dictionaries;

use Omnipay\Redsys\Dictionaries\PayMethods;
use Omnipay\Redsys\Dictionaries\TransactionTypes;
use PHPUnit\Framework\TestCase;

class PayMethodsTest extends TestCase
{
    public function testConstantValues()
    {
        $this->assertEquals('z', PayMethods::PAY_METHOD_BIZUM);
        $this->assertEquals('C', PayMethods::PAY_METHOD_CARD);
        $this->assertEquals('p', PayMethods::PAY_METHOD_PAYPAL);
        $this->assertEquals('R', PayMethods::PAY_METHOD_TRANSFER);
        $this->assertEquals('N', PayMethods::PAY_METHOD_MASTERPASS);
    }

    public function testSupportedOperationTypesReturnsArray()
    {
        $supported = PayMethods::supportedOperationTypes();

        $this->assertIsArray($supported);
    }

    public function testSupportedOperationTypesContainsAllPayMethods()
    {
        $supported = PayMethods::supportedOperationTypes();

        $this->assertArrayHasKey(PayMethods::PAY_METHOD_BIZUM, $supported);
        $this->assertArrayHasKey(PayMethods::PAY_METHOD_CARD, $supported);
        $this->assertArrayHasKey(PayMethods::PAY_METHOD_PAYPAL, $supported);
        $this->assertArrayHasKey(PayMethods::PAY_METHOD_TRANSFER, $supported);
        $this->assertArrayHasKey(PayMethods::PAY_METHOD_MASTERPASS, $supported);
    }

    public function testCardSupportsAllTransactionTypes()
    {
        $supported = PayMethods::supportedOperationTypes();

        $this->assertTrue($supported[PayMethods::PAY_METHOD_CARD]);
    }

    public function testBizumSupportedOperations()
    {
        $supported = PayMethods::supportedOperationTypes();
        $bizumOps = $supported[PayMethods::PAY_METHOD_BIZUM];

        $this->assertIsArray($bizumOps);
        $this->assertContains(TransactionTypes::AUTHORIZATION, $bizumOps);
        $this->assertContains(TransactionTypes::SPLIT_PREAUTHORIZATION, $bizumOps);
        $this->assertContains(TransactionTypes::SPLIT_CONFIRMATION, $bizumOps);
        $this->assertNotContains(TransactionTypes::REFUND, $bizumOps);
    }

    public function testPayPalSupportedOperations()
    {
        $supported = PayMethods::supportedOperationTypes();
        $paypalOps = $supported[PayMethods::PAY_METHOD_PAYPAL];

        $this->assertIsArray($paypalOps);
        $this->assertContains(TransactionTypes::AUTHORIZATION, $paypalOps);
        $this->assertContains(TransactionTypes::PREAUTHORIZATION, $paypalOps);
        $this->assertContains(TransactionTypes::PAYGOLD, $paypalOps);
    }

    public function testTransferSupportedOperations()
    {
        $supported = PayMethods::supportedOperationTypes();
        $transferOps = $supported[PayMethods::PAY_METHOD_TRANSFER];

        $this->assertIsArray($transferOps);
        $this->assertContains(TransactionTypes::AUTHORIZATION, $transferOps);
        $this->assertContains(TransactionTypes::PAYGOLD, $transferOps);
        $this->assertNotContains(TransactionTypes::PREAUTHORIZATION, $transferOps);
    }

    public function testMasterpassSupportedOperations()
    {
        $supported = PayMethods::supportedOperationTypes();
        $masterpassOps = $supported[PayMethods::PAY_METHOD_MASTERPASS];

        $this->assertIsArray($masterpassOps);
        $this->assertContains(TransactionTypes::AUTHORIZATION, $masterpassOps);
        $this->assertContains(TransactionTypes::SPLIT_PREAUTHORIZATION, $masterpassOps);
        $this->assertContains(TransactionTypes::PAYGOLD, $masterpassOps);
    }
}
