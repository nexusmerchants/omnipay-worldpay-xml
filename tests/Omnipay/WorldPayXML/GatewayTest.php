<?php

namespace Omnipay\WorldPayXML;

use Omnipay\Common\CreditCard;
use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    /** @var Gateway */
    protected $gateway;
    /** @var array */
    private $parameters;
    /** @var CreditCard */
    private $card;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = new Gateway(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );
        $this->gateway->setMerchant('ACMECO');
        $this->gateway->setTestMode(true);
        $this->gateway->setInstallation('ABC123');

        $this->parameters = [
            'amount' => '10.00',
            'card' => new CreditCard([
                'firstName' => 'Example',
                'lastName' => 'User',
                'number' => '4111111111111111',
                'expiryMonth' => '12',
                'expiryYear' => '2026',
                'cvv' => '123',
            ]),
            'transactionId' => 'T0211010',
        ];
    }

    public function testPurchaseSuccess()
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');

        $purchase = $this->gateway->purchase($this->parameters);

        // Confirm basic auth uses merchant code to authenticate when there's no username.
        $this->assertEquals('ACMECO', $purchase->getUsername());

        $response = $purchase->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('T0211010', $response->getTransactionReference());
    }

    public function testPurchaseError()
    {
        $this->setMockHttpResponse('PurchaseFailure.txt');

        $response = $this->gateway->purchase($this->parameters)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('CARD EXPIRED', $response->getMessage());
    }

    public function testApplePaySuccess()
    {
        $card = new ApplePayCreditCard();

        $this->assertEquals('apple', $card->getBrand());

        $applePayOptions = [
            'amount' => '10.00',
            'card' => new ApplePayCreditCard(),
            'appleToken' => [
                'transactionIdentifier' => '5394..00',
                'paymentData' => [
                    'data' => 'kdHd..GQ==',
                    'signature' => 'MIAGCSqGSIb3DQEH...AAA',
                    'version' => 'EC_v1',
                    'header' => [
                        'applicationData' => '94ee0..C2',
                        'ephemeralPublicKey' => 'MFkwE..Q==',
                        'publicKeyHash' => 'dxCK..6o=',
                        'transactionId' => 'd3b28af..f8',
                    ],
                ],
            ],
            'transactionId' => 'T0211010',
        ];

        $this->setMockHttpResponse('PurchaseSuccessApplePay.txt');

        $purchase = $this->gateway->purchase($applePayOptions);

        // Confirm basic auth uses merchant code to authenticate when there's no username.
        $this->assertEquals('ACMECO', $purchase->getUsername());

        $response = $purchase->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('T0211011', $response->getTransactionReference());
    }

    /**
     * Confirm basic auth uses a username when set rather than merchant code.
     */
    public function testUsernameAuthSetup()
    {
        $gatewayWithUsername = clone $this->gateway;
        $gatewayWithUsername->setUsername('MYSECRETUSERNAME987');

        $purchase = $gatewayWithUsername->purchase($this->parameters);
        $purchase->setCard($this->card);

        $this->assertEquals('MYSECRETUSERNAME987', $purchase->getUsername());
    }
}
