<?php

/**
 * LiqPay Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace LiqpayMagento\LiqPay\Sdk;

/** extends official LiqPay Sdk */
class LiqPay extends \LiqPay
{
    const VERSION = '3';
    const TEST_MODE_SURFIX_DELIM = '-';

    // success
    const STATUS_SUCCESS           = 'success';
    const STATUS_WAIT_COMPENSATION = 'wait_compensation';
    // const STATUS_SUBSCRIBED        = 'subscribed';
    const STATUS_WAIT_RESERVE      = 'wait_reserve';
    // processing
    const STATUS_PROCESSING  = 'processing';

    // failure
    const STATUS_FAILURE     = 'failure';
    const STATUS_ERROR       = 'error';

    const DEFAULT_CHECKOUT_URL = 'https://www.liqpay.ua/api/3/checkout';

    // wait
    const STATUS_WAIT_SECURE = 'wait_secure';
    const STATUS_WAIT_ACCEPT = 'wait_accept';
    const STATUS_WAIT_CARD   = 'wait_card';
    const STATUS_HOLD_WAIT   = 'hold_wait';
    
    // reversed
    const STATUS_REVERSED    = 'reversed';
    
    // sandbox
    const STATUS_SANDBOX     = 'sandbox';

    protected $_helper;

    public function __construct(
        \LiqpayMagento\LiqPay\Helper\Data $helper
    )
    {
        $this->_helper = $helper;
        if ($helper->isEnabled()) {
            $publicKey = $helper->getPublicKey();
            $privateKey = $helper->getPrivateKey();
            parent::__construct($publicKey, $privateKey);
        }
    }

    public function prepareParams($params)
    {
        if (!isset($params['sandbox'])) {
            $params['sandbox'] = (int)$this->_helper->isTestMode();
        }
        if (!isset($params['version'])) {
            $params['version'] = static::VERSION;
        }
        if (isset($params['order_id']) && $this->_helper->isTestMode()) {
            $surfix = $this->_helper->getTestOrderSurfix();
            if (!empty($surfix)) {
                $params['order_id'] .= self::TEST_MODE_SURFIX_DELIM . $surfix;
            }
        }
        return $params;
    }

    public function getHelper()
    {
        return $this->_helper;
    }

    public function getSupportedCurrencies()
    {
        return $this->_supportedCurrencies;
    }

    public function api($path, $params = array(), $timeout = 5)
    {
        $params = $this->prepareParams($params);
        return parent::api($path, $params, $timeout);
    }

    public function cnb_form($params)
    {
        $params = $this->prepareParams($params);
        return parent::cnb_form($params);
    }

    public function cnb_form_raw($params)
    {
        $params = $this->prepareParams($params);
        return parent::cnb_form_raw($params);
    }

    public function getDecodedData($data)
    {
        return json_decode(base64_decode($data), true, 1024);
    }

    public function checkSignature($signature, $data)
    {
        $privateKey = $this->_helper->getPrivateKey();
        $generatedSignature = base64_encode(sha1($privateKey . $data . $privateKey, 1));
        
        return $signature == $generatedSignature;
    }


    public function encode_params($params)
    {
        return base64_encode(json_encode($params));
    }

    public function cnb_params($params)
    {
        $params['public_key'] = $this->_helper->getPublicKey();

        if (!isset($params['version'])) {
            throw new \InvalidArgumentException('version is null');
        }
        if (!isset($params['amount'])) {
            throw new \InvalidArgumentException('amount is null');
        }
        if (!isset($params['currency'])) {
            throw new \InvalidArgumentException('currency is null');
        }
        if (!in_array($params['currency'], $this->_supportedCurrencies)) {
            throw new \InvalidArgumentException('currency is not supported');
        }
        if ($params['currency'] == self::CURRENCY_RUR) {
            $params['currency'] = self::CURRENCY_RUB;
        }
        if (!isset($params['description'])) {
            throw new \InvalidArgumentException('description is null');
        }

        return $params;
    }
}
