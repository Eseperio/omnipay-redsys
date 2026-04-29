<?php

namespace Omnipay\Redsys\Tests\Dictionaries;

use Omnipay\Redsys\Dictionaries\TransactionTypes;
use PHPUnit\Framework\TestCase;

class TransactionTypesTest extends TestCase
{
    public function testConstantValues()
    {
        $this->assertEquals(0, TransactionTypes::AUTHORIZATION);
        $this->assertEquals(1, TransactionTypes::PREAUTHORIZATION);
        $this->assertEquals(11, TransactionTypes::PREAUTHORIZATION_REPLACEMENT);
        $this->assertEquals(2, TransactionTypes::CONFIRMATION);
        $this->assertEquals(3, TransactionTypes::REFUND);
        $this->assertEquals(7, TransactionTypes::SPLIT_PREAUTHORIZATION);
        $this->assertEquals(8, TransactionTypes::SPLIT_CONFIRMATION);
        $this->assertEquals(9, TransactionTypes::CANCELLATION);
        $this->assertEquals(15, TransactionTypes::PAYGOLD);
        $this->assertEquals(17, TransactionTypes::CHIP_AUTHENTICATION);
        $this->assertEquals(34, TransactionTypes::REFUND_NO_ORIGINAL);
        $this->assertEquals(37, TransactionTypes::BETTING_PRIZE);
        $this->assertEquals(45, TransactionTypes::PAYMENT_CANCELLATION);
        $this->assertEquals(46, TransactionTypes::REFUND_CANCELLATION);
        $this->assertEquals(47, TransactionTypes::SPLIT_CONFIRMATION_CANCELLATION);
    }

    public function testAllConstantsAreIntegers()
    {
        $this->assertIsInt(TransactionTypes::AUTHORIZATION);
        $this->assertIsInt(TransactionTypes::PREAUTHORIZATION);
        $this->assertIsInt(TransactionTypes::CONFIRMATION);
        $this->assertIsInt(TransactionTypes::REFUND);
        $this->assertIsInt(TransactionTypes::PAYGOLD);
    }
}
