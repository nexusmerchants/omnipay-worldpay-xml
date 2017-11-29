<?php

namespace Omnipay\WorldPayXML\Message;

use DOMDocument;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * WorldPay XML Response
 */
class Response extends AbstractResponse
{
    /** @var string */
    const PAYMENT_STATUS_AUTHORISED             = 'AUTHORISED';
    /** @var string */
    const PAYMENT_STATUS_CAPTURED               = 'CAPTURED';
    /** @var string */
    const PAYMENT_STATUS_SETTLED_BY_MERCHANT    = 'SETTLED_BY_MERCHANT';
    /** @var string */
    const PAYMENT_STATUS_SENT_FOR_AUTHORISATION = 'SENT_FOR_AUTHORISATION';
    /** @var string */
    const PAYMENT_STATUS_CANCELLED              = 'CANCELLED';

    /**
     * @param RequestInterface $request Request
     * @param string           $data    Data
     * @throws InvalidResponseException if non-XML response received
     */
    public function __construct(RequestInterface $request, $data)
    {
        $this->request = $request;

        if (empty($data)) {
            throw new InvalidResponseException();
        }

        $responseDom = new DOMDocument;
        if (!@$responseDom->loadXML($data)) {
            throw new InvalidResponseException('Non-XML notification response received');
        }

        $this->data = @simplexml_import_dom(
            $responseDom->documentElement->firstChild // <notify> or <reply>
        );

        if (empty($this->data)) {
            throw new InvalidResponseException('Could not import response XML: ' . $data);
        }
    }

    /**
     * Get message
     *
     * @access public
     * @return string
     */
    public function getMessage()
    {
        $codes = [
            0  => 'AUTHORISED',
            2  => 'REFERRED',
            3  => 'INVALID ACCEPTOR',
            4  => 'HOLD CARD',
            5  => 'REFUSED',
            8  => 'APPROVE AFTER IDENTIFICATION',
            12 => 'INVALID TRANSACTION',
            13 => 'INVALID AMOUNT',
            14 => 'INVALID ACCOUNT',
            15 => 'INVALID CARD ISSUER',
            17 => 'ANNULATION BY CLIENT',
            19 => 'REPEAT OF LAST TRANSACTION',
            20 => 'ACQUIRER ERROR',
            21 => 'REVERSAL NOT PROCESSED, MISSING AUTHORISATION',
            24 => 'UPDATE OF FILE IMPOSSIBLE',
            25 => 'REFERENCE NUMBER CANNOT BE FOUND',
            26 => 'DUPLICATE REFERENCE NUMBER',
            27 => 'ERROR IN REFERENCE NUMBER FIELD',
            28 => 'ACCESS DENIED',
            29 => 'IMPOSSIBLE REFERENCE NUMBER',
            30 => 'FORMAT ERROR',
            31 => 'UNKNOWN ACQUIRER ACCOUNT CODE',
            33 => 'CARD EXPIRED',
            34 => 'FRAUD SUSPICION',
            38 => 'SECURITY CODE EXPIRED',
            40 => 'REQUESTED FUNCTION NOT SUPPORTED',
            41 => 'LOST CARD',
            43 => 'STOLEN CARD, PICK UP',
            51 => 'LIMIT EXCEEDED',
            55 => 'INVALID SECURITY CODE',
            56 => 'UNKNOWN CARD',
            57 => 'ILLEGAL TRANSACTION',
            58 => 'TRANSACTION NOT PERMITTED',
            62 => 'RESTRICTED CARD',
            63 => 'SECURITY RULES VIOLATED',
            64 => 'AMOUNT HIGHER THAN PREVIOUS TRANSACTION AMOUNT',
            68 => 'TRANSACTION TIMED OUT',
            75 => 'SECURITY CODE INVALID',
            76 => 'CARD BLOCKED',
            80 => 'AMOUNT NO LONGER AVAILABLE, AUTHORISATION EXPIRED',
            85 => 'REJECTED BY CARD ISSUER',
            91 => 'CREDITCARD ISSUER TEMPORARILY NOT REACHABLE',
            92 => 'CREDITCARD TYPE NOT PROCESSED BY ACQUIRER',
            94 => 'DUPLICATE REQUEST ERROR',
            97 => 'SECURITY BREACH'
        ];

        if (isset($this->data->error)) {
            return 'ERROR: ' . $this->data->error; // Cast to string to get CDATA content
        }

        $payment = $this->getOrder()->payment;
        if (isset($payment->ISO8583ReturnCode)) {
            $returnCode = $payment->ISO8583ReturnCode->attributes();

            foreach ($returnCode as $name => $value) {
                if ($name == 'code') {
                    return $codes[intval($value)];
                }
            }
        }

        if ($this->isSuccessful()) {
            return $codes[0];
        }

        return 'PENDING';
    }

    /**
     * Get transaction reference provided with order (the ID in Omnipay parlance), and sent back with notifications.
     *
     * @return string|null
     */
    public function getTransactionId()
    {
        if (empty($this->getOrder())) {
            return null;
        }

        $attributes = $this->getOrder()->attributes();

        if (isset($attributes['orderCode'])) {
            return $attributes['orderCode'];
        }

        return null;
    }

    /**
     * Get *your* reference a.k.a Omnipay transaction ID (!)
     *
     * @return string
     * @deprecated This was named inconsistently with other Omnipay adapters. Use getTransactionId(), whose name
     * reflects the actual contents returned.
     */
    public function getTransactionReference()
    {
        return $this->getTransactionId();
    }

    /**
     * @return string|null
     */
    public function getErrorCode()
    {
        if (!isset($this->data->error) || empty($this->data->error->attributes()['code'])) {
            return null;
        }

        return (string) $this->data->error->attributes()['code'];
    }

    /**
     * @return null|\SimpleXMLElement
     */
    public function getOrder()
    {
        if (isset($this->data->orderStatusEvent)) {
            return $this->data->orderStatusEvent; // Notifications
        }

        if (isset($this->data->orderStatus)) {
            return $this->data->orderStatus; // Order responses
        }

        return null;
    }

    /**
     * Get is redirect
     *
     * @access public
     * @return boolean
     */
    public function isRedirect()
    {
        return isset($this->data->requestInfo->request3DSecure->issuerURL);
    }

    /**
     * Whether transaction's last state indicates success
     *
     * @return bool
     */
    public function isSuccessful()
    {
        if (!isset($this->getOrder()->payment->lastEvent)) {
            return false;
        }

        return in_array(
            strtoupper($this->getOrder()->payment->lastEvent),
            [
                self::PAYMENT_STATUS_AUTHORISED,
                self::PAYMENT_STATUS_CAPTURED,
                self::PAYMENT_STATUS_SETTLED_BY_MERCHANT,
            ],
            true
        );
    }
}
