<?php

namespace Omnipay\Redsys\Tests\Message;

use Omnipay\Common\Http\Client;
use Omnipay\Redsys\Message\AbstractRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Concrete stub for testing the abstract Redsys AbstractRequest class.
 * Note: PurchaseRequest extends Omnipay\Common\Message\AbstractRequest directly,
 * so this stub is the only way to test the setAmount override in the Redsys AbstractRequest.
 */
class ConcreteRedsysRequestStub extends AbstractRequest
{
    public function getData()
    {
        return [];
    }

    public function sendData(mixed $data)
    {
        return null;
    }
}

class AbstractRequestTest extends TestCase
{
    /** @var ConcreteRedsysRequestStub */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();

        $httpClient = new Client();
        $httpRequest = Request::createFromGlobals();

        $this->request = new ConcreteRedsysRequestStub($httpClient, $httpRequest);
        $this->request->initialize(['currency' => 'EUR']);
    }

    public function testSetAmountFormatsDecimalWithNumberFormat()
    {
        // number_format(100.00, 2, '', '') = '10000'; stored '10000'
        // Money parses '10000' as EUR 10000.00; formatted '10000.00'
        $this->request->setAmount('100.00');

        $this->assertEquals('10000.00', $this->request->getAmount());
    }

    public function testSetAmountHandlesIntegerString()
    {
        // number_format('1000', 2, '', '') = '100000'; Money → 100000.00 EUR; formatted '100000.00'
        $this->request->setAmount('1000');

        $this->assertEquals('100000.00', $this->request->getAmount());
    }

    public function testSetAmountHandlesFloatInput()
    {
        // number_format('9.99', 2, '', '') = '999'; Money → 999.00 EUR; formatted '999.00'
        $this->request->setAmount('9.99');

        $this->assertEquals('999.00', $this->request->getAmount());
    }

    public function testSetAmountForTypicalRedsysInput()
    {
        // setAmount('10000') → number_format = '1000000'; Money → 1000000.00
        $this->request->setAmount('10000');

        $this->assertEquals('1000000.00', $this->request->getAmount());
    }

    public function testSetAmountNonNumericStringThrowsOnGetAmount()
    {
        // Non-numeric strings bypass number_format and are passed as-is to parent
        // parent::getAmount() will throw when Money tries to parse an invalid string
        $this->request->setAmount('not-a-number');

        $this->expectException(\Exception::class);
        $this->request->getAmount();
    }
}
