<?php

namespace Omnipay\Redsys\Tests\Exception;

use Omnipay\Redsys\Exception\BadPayMethodException;
use Omnipay\Redsys\Exception\BadSignatureException;
use Omnipay\Redsys\Exception\CallbackException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testBadPayMethodExceptionIsException()
    {
        $e = new BadPayMethodException();

        $this->assertInstanceOf(\Exception::class, $e);
    }

    public function testBadPayMethodExceptionDefaultMessage()
    {
        $e = new BadPayMethodException();

        $this->assertEquals('Wrong pay method selected', $e->getMessage());
    }

    public function testBadPayMethodExceptionCanBeThrown()
    {
        $this->expectException(BadPayMethodException::class);

        throw new BadPayMethodException();
    }

    public function testBadSignatureExceptionIsException()
    {
        $e = new BadSignatureException();

        $this->assertInstanceOf(\Exception::class, $e);
    }

    public function testBadSignatureExceptionDefaultMessage()
    {
        $e = new BadSignatureException();

        $this->assertEquals('Invalid signature', $e->getMessage());
    }

    public function testBadSignatureExceptionCanBeThrown()
    {
        $this->expectException(BadSignatureException::class);

        throw new BadSignatureException();
    }

    public function testCallbackExceptionIsException()
    {
        $e = new CallbackException();

        $this->assertInstanceOf(\Exception::class, $e);
    }

    public function testCallbackExceptionDefaultMessage()
    {
        $e = new CallbackException();

        $this->assertEquals('Redsys callback returned an error status code', $e->getMessage());
    }

    public function testCallbackExceptionCanBeThrown()
    {
        $this->expectException(CallbackException::class);

        throw new CallbackException();
    }

    public function testCallbackExceptionAcceptsCodeParameter()
    {
        $e = new CallbackException(null, 101);

        $this->assertEquals(101, $e->getCode());
    }
}
