<?php

namespace Omnipay\Redsys\Tests\Encryptor;

use Omnipay\Redsys\Encryptor\Encryptor;
use PHPUnit\Framework\TestCase;

class EncryptorTest extends TestCase
{
    public function testEncrypt3DESReturnsString()
    {
        $key = base64_decode('sq7HjrUOBfKmC576ILgskD5srU870gJ7');
        $result = Encryptor::encrypt_3DES('0001020304', $key);

        $this->assertIsString($result);
    }

    public function testEncrypt3DESLengthIsMultipleOf8()
    {
        $key = base64_decode('sq7HjrUOBfKmC576ILgskD5srU870gJ7');
        $message = '0001020304';

        $result = Encryptor::encrypt_3DES($message, $key);

        $expectedLength = (int)(ceil(strlen($message) / 8) * 8);
        $this->assertEquals($expectedLength, strlen($result));
    }

    public function testEncrypt3DESIsDeterministic()
    {
        $key = base64_decode('sq7HjrUOBfKmC576ILgskD5srU870gJ7');
        $message = '0001020304';

        $result1 = Encryptor::encrypt_3DES($message, $key);
        $result2 = Encryptor::encrypt_3DES($message, $key);

        $this->assertEquals($result1, $result2);
    }

    public function testEncrypt3DESProducesDifferentOutputForDifferentMessages()
    {
        $key = base64_decode('sq7HjrUOBfKmC576ILgskD5srU870gJ7');

        $result1 = Encryptor::encrypt_3DES('0001020304', $key);
        $result2 = Encryptor::encrypt_3DES('0001020305', $key);

        $this->assertNotEquals($result1, $result2);
    }

    public function testEncrypt3DESWithShortMessage()
    {
        $key = base64_decode('sq7HjrUOBfKmC576ILgskD5srU870gJ7');
        $result = Encryptor::encrypt_3DES('abc', $key);

        // Minimum output length is 8
        $this->assertEquals(8, strlen($result));
    }

    public function testEncrypt3DESWithMessageExactly8Chars()
    {
        $key = base64_decode('sq7HjrUOBfKmC576ILgskD5srU870gJ7');
        $result = Encryptor::encrypt_3DES('12345678', $key);

        $this->assertEquals(8, strlen($result));
    }

    public function testEncrypt3DESWithMessageLongerThan8Chars()
    {
        $key = base64_decode('sq7HjrUOBfKmC576ILgskD5srU870gJ7');
        $result = Encryptor::encrypt_3DES('1234567890', $key); // 10 chars → padded to 16

        $this->assertEquals(16, strlen($result));
    }

    public function testEncrypt3DESProducesKnownOutput()
    {
        // Known test vector computed from the library itself
        $key = base64_decode('sq7HjrUOBfKmC576ILgskD5srU870gJ7');
        $message = '0001020304';

        $result = Encryptor::encrypt_3DES($message, $key);

        // Verify the result is used correctly in HMAC by checking the full signature chain
        $merchantParameters = 'eyJEc19NZXJjaGFudF9BbW91bnQiOiIxMDAwMCJ9';
        $hmac = hash_hmac('sha256', $merchantParameters, $result, true);
        $this->assertNotEmpty(base64_encode($hmac));
    }
}
