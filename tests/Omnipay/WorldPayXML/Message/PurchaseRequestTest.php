<?php

namespace Omnipay\WorldPayXML\Tests\Message;

use Omnipay\Common\CreditCard;
use Omnipay\Tests\TestCase;
use Omnipay\WorldPayXML\ApplePayCreditCard;
use Omnipay\WorldPayXML\Message\PurchaseRequest;

class PurchaseRequestTest extends TestCase
{
    /** @var PurchaseRequest */
    private $purchase;

    public function setUp()
    {
        parent::setUp();

        $this->purchase = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->purchase->setAmount(7.45);
        $this->purchase->setCurrency('GBP');
    }

    public function testDirectCardPayload()
    {
        $this->purchase->setCard(new CreditCard([
            'billingFirstName' => 'Vince',
            'billingLastName' => 'Staples',
            'shippingFirstName' => 'Vince',
            'shippingLastName' => 'Staples',
            'email' => 'cr+vs@noellh.com',
            'address1' => '745 THORNBURY CLOSE',
            'address2' => '',
            'city' => 'LONDON',
            'country' => 'GB',
            'postcode' => 'N16 8UX',
            'number' => '4111111111111111',
            'expiryMonth' => '12',
            'expiryYear' => '2026',
            'cvv' => '123',
        ]));

        $data = $this->purchase->getData();

        $this->assertEquals('745', $data->submit->order->amount->attributes()['value']);
        $this->assertEquals('GBP', $data->submit->order->amount->attributes()['currencyCode']);
        $this->assertEquals('2', $data->submit->order->amount->attributes()['exponent']);
        $this->assertEquals('745 THORNBURY CLOSE', (string) $data->submit->order->paymentDetails->{'VISA-SSL'}->cardAddress->address->street);
        $this->assertEquals('N16 8UX', (string) $data->submit->order->paymentDetails->{'VISA-SSL'}->cardAddress->address->postalCode);
        $this->assertEquals('GB', (string) $data->submit->order->paymentDetails->{'VISA-SSL'}->cardAddress->address->countryCode);

        $this->assertEquals('4111111111111111', (string) $data->submit->order->paymentDetails->{'VISA-SSL'}->cardNumber);
        $this->assertEquals('12', (string) $data->submit->order->paymentDetails->{'VISA-SSL'}->expiryDate->date->attributes()['month']);
        $this->assertEquals('2026', (string) $data->submit->order->paymentDetails->{'VISA-SSL'}->expiryDate->date->attributes()['year']);
        $this->assertEquals('123', (string) $data->submit->order->paymentDetails->{'VISA-SSL'}->cvc);
    }

    public function testApplePayPayload()
    {
        $this->purchase->setCard(new ApplePayCreditCard());
        $this->purchase->setAppleToken([
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
        ]);

        $data = $this->purchase->getData();

        $this->assertEquals('745', $data->submit->order->amount->attributes()['value']);
        $this->assertEquals('GBP', $data->submit->order->amount->attributes()['currencyCode']);
        $this->assertEquals('2', $data->submit->order->amount->attributes()['exponent']);

        $this->assertEquals('kdHd..GQ==', $data->submit->order->paymentDetails->{'APPLEPAY-SSL'}->data);
        $this->assertEquals('94ee0..C2', $data->submit->order->paymentDetails->{'APPLEPAY-SSL'}->header->applicationData);
        $this->assertEquals('MFkwE..Q==', $data->submit->order->paymentDetails->{'APPLEPAY-SSL'}->header->ephemeralPublicKey);
        $this->assertEquals('dxCK..6o=', $data->submit->order->paymentDetails->{'APPLEPAY-SSL'}->header->publicKeyHash);
        $this->assertEquals('d3b28af..f8', $data->submit->order->paymentDetails->{'APPLEPAY-SSL'}->header->transactionId);
        $this->assertEquals('MIAGCSqGSIb3DQEH...AAA', $data->submit->order->paymentDetails->{'APPLEPAY-SSL'}->signature);
        $this->assertEquals('EC_v1', $data->submit->order->paymentDetails->{'APPLEPAY-SSL'}->version);

        $this->assertEmpty($data->submit->order->billingAddress);
    }
}
