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

        $visaDetails = $data->submit->order->paymentDetails->{'VISA-SSL'};
        $this->assertEquals('745 THORNBURY CLOSE', $visaDetails->cardAddress->address->street);
        $this->assertEquals('N16 8UX', $visaDetails->cardAddress->address->postalCode);
        $this->assertEquals('GB', $visaDetails->cardAddress->address->countryCode);

        $this->assertEquals('4111111111111111', $visaDetails->cardNumber);
        $this->assertEquals('12', $visaDetails->expiryDate->date->attributes()['month']);
        $this->assertEquals('2026', $visaDetails->expiryDate->date->attributes()['year']);
        $this->assertEquals('123', $visaDetails->cvc);
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

        $appleDetails = $data->submit->order->paymentDetails->{'APPLEPAY-SSL'};
        $this->assertEquals('kdHd..GQ==', $appleDetails->data);
        $this->assertEquals('94ee0..C2', $appleDetails->header->applicationData);
        $this->assertEquals('MFkwE..Q==', $appleDetails->header->ephemeralPublicKey);
        $this->assertEquals('dxCK..6o=', $appleDetails->header->publicKeyHash);
        $this->assertEquals('d3b28af..f8', $appleDetails->header->transactionId);
        $this->assertEquals('MIAGCSqGSIb3DQEH...AAA', $appleDetails->signature);
        $this->assertEquals('EC_v1', $appleDetails->version);

        $this->assertEmpty($data->submit->order->billingAddress);
    }
}
