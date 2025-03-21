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
use LiqpayMagento\LiqPay\Block\SubmitForm;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use LiqpayMagento\LiqPay\Helper\Data as Helper;


class Form extends Action
{
    /**
     * @var \Magento\Checkout\Model\Session
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

    private $_localeResolver;

    public function __construct(
        Context           $context,
        CheckoutSession   $checkoutSession,
        Helper            $helper,
        LayoutFactory     $layoutFactory,
        ResolverInterface $resolverLocale
    )
    {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_layoutFactory = $layoutFactory;
        $this->_localeResolver = $resolverLocale;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        try {
            if (!$this->_helper->isEnabled()) {
                throw new \Exception(__('Payment is not allow.'));
            }
            $order = $this->getCheckoutSession()->getLastRealOrder();
            if (!($order && $order->getId())) {
                throw new \Exception(__('Order not found'));
            }
            if ($this->_helper->checkOrderIsLiqPayPayment($order)) {
                /* @var $formBlock \LiqpayMagento\LiqPay\Block\SubmitForm */
                $formBlock = $this->_layoutFactory->create()->createBlock(SubmitForm::class);
                $formBlock->setOrder($order);
                $formBlock->setLanguage($this->getLanguageCode());
                $content = $formBlock->toHtml();

                $data = [
                    'status' => 'success',
                    'content' => $content,
                ];
            } else {
                throw new \Exception(__('Order payment method is not a LiqPay payment method'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong, please try again later'));
            $this->_helper->getLogger()->critical($e);
            $this->getCheckoutSession()->restoreQuote();
            $data = [
                'status' => 'error',
                'redirect' => $this->_url->getUrl('checkout/cart'),
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData($data);
        return $result;
    }


    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * @return string
     */
    private function getLanguageCode(): string
    {
        $locale = $this->_localeResolver->getLocale();
        $code = 'en';
        if ($locale === 'ru_RU') {
            $code = 'ru';
        } elseif ($locale === 'uk_UA') {
            $code = 'ua';
        }
        return $code;
    }
}
