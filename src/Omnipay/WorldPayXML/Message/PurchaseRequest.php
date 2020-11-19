<?php

namespace Omnipay\WorldPayXML\Message;

use Guzzle\Plugin\Cookie\Cookie;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Omnipay\Common\CreditCard;
use Omnipay\Common\Message\AbstractRequest;

/**
 * Omnipay WorldPay XML Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{
    const EP_HOST_LIVE = 'https://secure.worldpay.com';
    const EP_HOST_TEST = 'https://secure-test.worldpay.com';

    const EP_PATH = '/jsp/merchant/xml/paymentService.jsp';

    const VERSION = '1.4';

    /**
     * @var \Guzzle\Plugin\Cookie\CookiePlugin
     */
    protected $cookiePlugin;

    /**
     * Get accept header
     *
     * @return string
     */
    public function getAcceptHeader()
    {
        return $this->getParameter('acceptHeader');
    }

    /**
     * Set accept header
     *
     * @param string $value Accept header
     * @return PurchaseRequest
     */
    public function setAcceptHeader($value)
    {
        return $this->setParameter('acceptHeader', $value);
    }

    /**
     * Get cookie plugin
     *
     * @return \Guzzle\Plugin\Cookie\CookiePlugin
     */
    public function getCookiePlugin()
    {
        return $this->cookiePlugin;
    }

    /**
     * Get installation
     *
     * @return string
     */
    public function getInstallation()
    {
        return $this->getParameter('installation');
    }

    /**
     * Set installation
     *
     * @param string $value Installation
     * @return PurchaseRequest
     */
    public function setInstallation($value)
    {
        return $this->setParameter('installation', $value);
    }

    /**
     * Get merchant
     *
     * @return string
     */
    public function getMerchant()
    {
        return $this->getParameter('merchant');
    }

    /**
     * Set merchant
     *
     * @param string $value Merchant
     * @return PurchaseRequest
     */
    public function setMerchant($value)
    {
        return $this->setParameter('merchant', $value);
    }

    /**
     * Get the separate username if configured (more secure approach for basic auth) or fallback to merchant if not
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->parameters->get('username', $this->getParameter('merchant'));
    }

    /**
     * Set basic auth username
     *
     * @param string $value
     * @return AbstractRequest
     */
    public function setUsername($value)
    {
        return $this->setParameter('username', $value);
    }

    /**
     * Get pa response
     *
     * @return string
     */
    public function getPaResponse()
    {
        return $this->getParameter('pa_response');
    }

    /**
     * Set pa response
     *
     * @param string $value Pa response
     * @return PurchaseRequest
     */
    public function setPaResponse($value)
    {
        return $this->setParameter('pa_response', $value);
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->getParameter('password');
    }

    /**
     * Set password
     *
     * @param string $value Password
     * @return PurchaseRequest
     */
    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }

    /**
     * Get redirect cookie
     *
     * @return string
     */
    public function getRedirectCookie()
    {
        return $this->getParameter('redirect_cookie');
    }

    /**
     * Set redirect cookie
     *
     * @param string $value Password
     * @return PurchaseRequest
     */
    public function setRedirectCookie($value)
    {
        return $this->setParameter('redirect_cookie', $value);
    }

    /**
     * Get redirect echo
     *
     * @return string
     */
    public function getRedirectEcho()
    {
        return $this->getParameter('redirect_echo');
    }

    /**
     * Set redirect echo
     *
     * @param string $value Password
     * @return PurchaseRequest
     */
    public function setRedirectEcho($value)
    {
        return $this->setParameter('redirect_echo', $value);
    }

    /**
     * Get session
     *
     * @return string
     */
    public function getSession()
    {
        return $this->getParameter('session');
    }

    /**
     * Set session
     *
     * @param string $value Session
     * @return PurchaseRequest
     */
    public function setSession($value)
    {
        return $this->setParameter('session', $value);
    }

    /**
     * Get term url
     *
     * @return string
     */
    public function getTermUrl()
    {
        return $this->getParameter('termUrl');
    }

    /**
     * Set term url
     *
     * @param string $value Term url
     * @return PurchaseRequest
     */
    public function setTermUrl($value)
    {
        return $this->setParameter('termUrl', $value);
    }

    /**
     * Get user agent header
     *
     * @return string
     */
    public function getUserAgentHeader()
    {
        return $this->getParameter('userAgentHeader');
    }

    /**
     * Set user agent header
     *
     * @param string $value User agent header
     * @return PurchaseRequest
     */
    public function setUserAgentHeader($value)
    {
        return $this->setParameter('userAgentHeader', $value);
    }

    /**
     * @param $appleToken
     * @return PurchaseRequest
     */
    public function setAppleToken($appleToken)
    {
        return $this->setParameter('appleToken', $appleToken);
    }

    /**
     * @return array
     */
    public function getAppleToken()
    {
        return $this->getParameter('appleToken');
    }

    /**
     * Get data
     *
     * @return \SimpleXMLElement
     */
    public function getData()
    {
        $this->validate('amount', 'card');

        if (!$this->getAppleToken()) {
            $this->getCard()->validate();
        } // Else for Apple Pay, we use a dummy 'card' with partial metadata, which won't validate.

        $data = new \SimpleXMLElement('<paymentService />');
        $data->addAttribute('version', self::VERSION);
        $data->addAttribute('merchantCode', $this->getMerchant());

        $order = $data->addChild('submit')->addChild('order');
        $order->addAttribute('orderCode', $this->getTransactionId());

        $installationId = $this->getInstallation();
        if (!empty($installationId)) {
            $order->addAttribute('installationId', $this->getInstallation());
        }

        $description = $this->getDescription() ? $this->getDescription() : 'Merchandise';
        $order->addChild('description', $description);

        $amount = $order->addChild('amount');
        $amount->addAttribute('value', $this->getAmountInteger());
        $amount->addAttribute('currencyCode', $this->getCurrency());
        $amount->addAttribute('exponent', $this->getCurrencyDecimalPlaces());

        $payment = $order->addChild('paymentDetails');

        $codes = [
            CreditCard::BRAND_AMEX        => 'AMEX-SSL',
            CreditCard::BRAND_DANKORT     => 'DANKORT-SSL',
            CreditCard::BRAND_DINERS_CLUB => 'DINERS-SSL',
            CreditCard::BRAND_DISCOVER    => 'DISCOVER-SSL',
            CreditCard::BRAND_JCB         => 'JCB-SSL',
            CreditCard::BRAND_LASER       => 'LASER-SSL',
            CreditCard::BRAND_MAESTRO     => 'MAESTRO-SSL',
            CreditCard::BRAND_MASTERCARD  => 'ECMC-SSL',
            CreditCard::BRAND_SWITCH      => 'MAESTRO-SSL',
            CreditCard::BRAND_VISA        => 'VISA-SSL'
        ];

        if ($this->getCard()->getBrand() === 'apple') {
            // With Apple Pay we have no card number so can't pattern match via getBrand()
            $card = $payment->addChild('APPLEPAY-SSL');

            $appleData = $this->getAppleToken()['paymentData'];

            $header = $card->addChild('header');

            $header->addChild('ephemeralPublicKey', $appleData['header']['ephemeralPublicKey']);
            $header->addChild('publicKeyHash', $appleData['header']['publicKeyHash']);
            $header->addChild('transactionId', $appleData['header']['transactionId']);
            if (isset($appleData['header']['applicationData'])) {
                $header->addChild('applicationData', $appleData['header']['applicationData']);
            }

            $card->addChild('signature', $appleData['signature']);
            $card->addChild('version', $appleData['version']);
            $card->addChild('data', $appleData['data']);
        } else {
            $card = $payment->addChild($codes[$this->getCard()->getBrand()]);
            $card->addChild('cardNumber', $this->getCard()->getNumber());

            $expiry = $card->addChild('expiryDate')->addChild('date');
            $expiry->addAttribute('month', $this->getCard()->getExpiryDate('m'));
            $expiry->addAttribute('year', $this->getCard()->getExpiryDate('Y'));

            $card->addChild('cardHolderName', $this->getCard()->getName());

            if (
                $this->getCard()->getBrand() == CreditCard::BRAND_MAESTRO
                || $this->getCard()->getBrand() == CreditCard::BRAND_SWITCH
            ) {
                $start = $card->addChild('startDate')->addChild('date');
                $start->addAttribute('month', $this->getCard()->getStartDate('m'));
                $start->addAttribute('year', $this->getCard()->getStartDate('Y'));

                $card->addChild('issueNumber', $this->getCard()->getIssueNumber());
            }

            $card->addChild('cvc', $this->getCard()->getCvv());

            $address = $card->addChild('cardAddress')->addChild('address');
            $address->addChild('address1', $this->getCard()->getAddress1());
            $address->addChild('address2', $this->getCard()->getAddress2());
            $address->addChild('postalCode', $this->getCard()->getPostcode());
            $address->addChild('city', $this->getCard()->getCity());
            $address->addChild('state', $this->getCard()->getState());
            $address->addChild('countryCode', $this->getCard()->getCountry());

            $session = $payment->addChild('session'); // Empty tag is valid but setting an empty ID attr isn't
            if ($this->getClientIp() && $this->getSession()) {
                $session->addAttribute('shopperIPAddress', $this->getClientIP());
                $session->addAttribute('id', $this->getSession());
            }
        }

        $paResponse = $this->getPaResponse();

        if (!empty($paResponse)) {
            $info3DSecure = $payment->addChild('info3DSecure');
            $info3DSecure->addChild('paResponse', $paResponse);
        }

        $shopper = $order->addChild('shopper');

        $email = $this->getCard()->getEmail();

        if (!empty($email)) {
            $shopper->addChild(
                'shopperEmailAddress',
                $this->getCard()->getEmail()
            );
        }

        $browser = $shopper->addChild('browser');
        $browser->addChild('acceptHeader', $this->getAcceptHeader());
        $browser->addChild('userAgentHeader', $this->getUserAgentHeader());

        $echoData = $this->getRedirectEcho();

        if (!empty($echoData)) {
            $order->addChild('echoData', $echoData);
        }

        return $data;
    }

    /**
     * Send data
     *
     * @param \SimpleXMLElement $data Data
     * @return RedirectResponse
     */
    public function sendData($data)
    {
        $implementation = new \DOMImplementation();

        $dtd = $implementation->createDocumentType(
            'paymentService',
            '-//WorldPay//DTD WorldPay PaymentService v1//EN',
            'http://dtd.worldpay.com/paymentService_v1.dtd'
        );

        $document = $implementation->createDocument(null, '', $dtd);
        $document->encoding = 'utf-8';

        $node = $document->importNode(dom_import_simplexml($data), true);
        $document->appendChild($node);

        $authorisation = base64_encode(
            $this->getUsername() . ':' . $this->getPassword()
        );

        $headers = [
            'Authorization' => 'Basic ' . $authorisation,
            'Content-Type'  => 'text/xml; charset=utf-8'
        ];

        $cookieJar = new ArrayCookieJar();

        $redirectCookie = $this->getRedirectCookie();

        if (!empty($redirectCookie)) {
            $url = parse_url($this->getEndpoint());

            $cookieJar->add(
                new Cookie([
                    'domain' => $url['host'],
                    'name'   => 'machine',
                    'path'   => '/',
                    'value'  => $redirectCookie
                ])
            );
        }

        $this->cookiePlugin = new CookiePlugin($cookieJar);

        //$this->httpClient->addSubscriber($this->cookiePlugin);

        $xml = $document->saveXML();

        $httpResponse = $this->httpClient
            ->request('POST', $this->getEndpoint(), $headers, $xml);

        if ($this->getCard()->getBrand() === 'apple') {
            $this->response = new Response($this, $httpResponse->getBody()->getContents());
        } else {
            $this->response = new RedirectResponse($this, $httpResponse->getBody()->getContents());
        }

        return $this->response;
    }

    /**
     * Get endpoint
     *
     * Returns endpoint depending on test mode
     *
     * @return string
     */
    protected function getEndpoint()
    {
        if ($this->getTestMode()) {
            return self::EP_HOST_TEST . self::EP_PATH;
        }

        return self::EP_HOST_LIVE . self::EP_PATH;
    }
}
