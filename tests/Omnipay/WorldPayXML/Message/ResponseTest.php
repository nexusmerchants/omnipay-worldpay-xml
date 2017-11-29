<?php

namespace Omnipay\WorldPayXML\Message;

use Omnipay\Tests\TestCase;

class ResponseTest extends TestCase
{
    /**
     * @expectedException \Omnipay\Common\Exception\InvalidResponseException
     */
    public function testConstructEmpty()
    {
        $response = new Response($this->getMockRequest(), '');
    }

    public function testPurchaseSuccess()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseSuccess.txt');
        $response = new Response(
            $this->getMockRequest(),
            $httpResponse->getBody()
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('T0211010', $response->getTransactionReference());
        $this->assertEquals('AUTHORISED', $response->getMessage());
    }

    public function testPurchaseSuccessApplePay()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseSuccessApplePay.txt');
        $response = new Response(
            $this->getMockRequest(),
            $httpResponse->getBody()
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('T0211011', $response->getTransactionReference());
        $this->assertEquals('AUTHORISED', $response->getMessage());
    }

    public function testPurchaseFailure()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseFailure.txt');
        $response = new Response(
            $this->getMockRequest(),
            $httpResponse->getBody()
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('T0211234', $response->getTransactionReference());
        $this->assertSame('CARD EXPIRED', $response->getMessage());
    }

    public function testPurchaseErrorGeneric()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseErrorGeneric.txt');

        $response = new Response(
            $this->getMockRequest(),
            $httpResponse->getBody()
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('ERROR: Nasty internal error!', $response->getMessage());
        $this->assertNull($response->getErrorCode());
    }

    public function testPurchaseErrorDuplicateOrder()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseErrorDuplicateOrder.txt');

        $response = new Response(
            $this->getMockRequest(),
            $httpResponse->getBody()
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('ERROR: Duplicate Order', $response->getMessage());
        $this->assertSame('5', $response->getErrorCode());
    }

    /**
     * You can get this e.g. if you are authenticated but your merchant code in the body is wrong.
     */
    public function testPurchaseErrorSecurityFailure()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseErrorSecurityViolation.txt');

        $response = new Response(
            $this->getMockRequest(),
            $httpResponse->getBody()
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('ERROR: Security violation', $response->getMessage());
        $this->assertSame('4', $response->getErrorCode());
    }

    /**
     * @expectedException \Omnipay\Common\Exception\InvalidResponseException
     * @expectedExceptionMessage Could not import response XML:
     */
    public function testPurchaseUnauthenticated()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseUnauthenticated.txt');

        new Response(
            $this->getMockRequest(),
            $httpResponse->getBody()
        );
    }
}
