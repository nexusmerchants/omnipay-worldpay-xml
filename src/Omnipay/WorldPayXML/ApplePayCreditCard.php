<?php

namespace Omnipay\WorldPayXML;

use Omnipay\Common\CreditCard;

/**
 * Class ApplePayCreditCard
 * @package Omnipay\WorldPayXML
 *
 * Pseudo credit card for Apple Pay - allows us to get an appropriate 'brand' without checking the card
 * number pattern, since we don't have a card number.
 */
class ApplePayCreditCard extends CreditCard
{
    public function getBrand()
    {
        return 'apple';
    }
}
