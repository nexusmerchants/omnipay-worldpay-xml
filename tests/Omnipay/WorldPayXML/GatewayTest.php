<?php

namespace Omnipay\WorldPayXML;

use Omnipay\Common\CreditCard;
use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new Gateway(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        $this->options = array(
            'amount' => '10.00',
            'card' => new CreditCard(
                array(
                    'firstName' => 'Example',
                    'lastName' => 'User',
                    'number' => '4111111111111111',
                    'expiryMonth' => '12',
                    'expiryYear' => '2026',
                    'cvv' => '123',
                )
            ),
            'transactionId' => 'T0211010',
        );
    }

    public function testPurchaseSuccess()
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');

        $response = $this->gateway->purchase($this->options)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('T0211010', $response->getTransactionReference());
    }

    public function testPurchaseError()
    {
        $this->setMockHttpResponse('PurchaseFailure.txt');

        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('CARD EXPIRED', $response->getMessage());
    }

    public function testApplePaySuccess()
    {
        $card = new ApplePayCreditCard();

        $this->assertEquals('apple', $card->getBrand());

        $applePayOptions = array(
            'amount' => '10.00',
            'card' => new ApplePayCreditCard(),
            'appleToken' => array(
                'transactionIdentifier' => '5394..00',
                'paymentData' => array(
                    'data' => 'kdHd..GQ==',
                    'signature' => 'MIAGCSqGSIb3DQEH...AAA',
                    'version' => 'EC_v1',
                    'header' => array(
                        'applicationData' => '94ee0..C2',
                        'ephemeralPublicKey' => 'MFkwE..Q==',
                        'publicKeyHash' => 'dxCK..6o=',
                        'transactionId' => 'd3b28af..f8',
                    ),
                ),
            ),
            'transactionId' => 'T0211010',
        );

        $this->setMockHttpResponse('PurchaseSuccessApplePay.txt');

        $response = $this->gateway->purchase($applePayOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('T0211011', $response->getTransactionReference());
    }
}
