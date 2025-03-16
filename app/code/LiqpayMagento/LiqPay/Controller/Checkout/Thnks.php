<?php

/**
 * LiqPay Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace LiqpayMagento\LiqPay\Controller\Checkout;

use Exception;
use LiqpayMagento\LiqPay\Helper\Data as Helper;
use LiqpayMagento\LiqPay\Model\LiqPayCallback;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\LayoutFactory;

class Thnks extends Action
{
    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var LayoutFactory
     */
    protected $_layoutFactory;

    /**
     * @var LiqPayCallback
     */
    private LiqPayCallback $callback;

    public function __construct(
        Context         $context,
        CheckoutSession $checkoutSession,
        Helper          $helper,
        LayoutFactory   $layoutFactory,
        LiqPayCallback  $callback
    )
    {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_layoutFactory = $layoutFactory;
        $this->callback = $callback;
    }

    /**
     * Dispatch request
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $frontendUrl = $this->_helper->getFrontendWebsiteUrl();
//        if ($this->getRequest()->getParam('language', false)) {
//            $frontendUrl = $frontendUrl . '/' . $this->getRequest()->getParam('language') . '/';
//        }

        try {
            if (!$this->getRequest()->isPost()) {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($frontendUrl);
                return $resultRedirect;
            }

            $this->callback->callback();
        } catch (Exception $e) {

        }

        if ($this->getRequest()->getParam('orderNumber', false)) {
            $number = $this->getRequest()->getParam('orderNumber');
            $this->_checkoutSession->setLiqPayLastOrder($number);
            $frontendUrl = $frontendUrl . \Dragonfly\BootstrapCheckout\Api\BoostrapCheckoutInterface::URL_KEY_ORDER_SUCCESS;
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($frontendUrl);
        return $resultRedirect;
    }


    /**
     * Return checkout session object
     *
     * @return CheckoutSession
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}
