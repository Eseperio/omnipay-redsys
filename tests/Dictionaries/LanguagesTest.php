<?php

namespace Omnipay\Redsys\Tests\Dictionaries;

use Omnipay\Redsys\Dictionaries\Languages;
use PHPUnit\Framework\TestCase;

class LanguagesTest extends TestCase
{
    public function testConstantValues()
    {
        $this->assertEquals('001', Languages::SPANISH);
        $this->assertEquals('002', Languages::ENGLISH);
        $this->assertEquals('003', Languages::CATALAN);
        $this->assertEquals('004', Languages::FRENCH);
        $this->assertEquals('005', Languages::GERMAN);
        $this->assertEquals('006', Languages::DUTCH);
        $this->assertEquals('007', Languages::ITALIAN);
        $this->assertEquals('008', Languages::SWEDISH);
        $this->assertEquals('009', Languages::PORTUGUESE);
        $this->assertEquals('010', Languages::VALENCIAN);
        $this->assertEquals('011', Languages::POLISH);
        $this->assertEquals('012', Languages::GALICIAN);
        $this->assertEquals('013', Languages::BASQUE);
    }

    public function testAllConstantsAreStrings()
    {
        $this->assertIsString(Languages::SPANISH);
        $this->assertIsString(Languages::ENGLISH);
        $this->assertIsString(Languages::GERMAN);
    }

    public function testAllConstantsAreNumericStrings()
    {
        $this->assertTrue(is_numeric(Languages::SPANISH));
        $this->assertTrue(is_numeric(Languages::ENGLISH));
        $this->assertTrue(is_numeric(Languages::BASQUE));
    }

    public function testAllConstantsAreThreeCharacters()
    {
        $this->assertEquals(3, strlen(Languages::SPANISH));
        $this->assertEquals(3, strlen(Languages::ENGLISH));
        $this->assertEquals(3, strlen(Languages::BASQUE));
    }

    public function testConstantsAreZeroPadded()
    {
        // Single-digit codes should be zero-padded (e.g., '001' not '1')
        $this->assertStringStartsWith('0', Languages::SPANISH);
        $this->assertStringStartsWith('0', Languages::ENGLISH);
    }
}
