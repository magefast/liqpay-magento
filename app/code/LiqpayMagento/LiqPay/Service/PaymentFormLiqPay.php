<?php

namespace LiqpayMagento\LiqPay\Service;

use LiqpayMagento\LiqPay\Helper\Data as Helper;
use LiqpayMagento\LiqPay\Sdk\LiqPay;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderFactory;

class PaymentFormLiqPay
{
    /**
     * @var LiqPay
     */
    private LiqPay $liqPay;

    /**
     * @var Helper
     */
    private Helper $helper;

    /**
     * @var OrderFactory
     */
    private OrderFactory $orderFactory;

    /**
     * @param LiqPay $liqPay
     * @param Helper $helper
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        LiqPay       $liqPay,
        Helper       $helper,
        OrderFactory $orderFactory
    )
    {
        $this->liqPay = $liqPay;
        $this->helper = $helper;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param $orderId
     * @return array
     */
    public function execute($orderId, $language = 'ua'): array
    {
        $formData = [
            'action' => $this->liqPay::DEFAULT_CHECKOUT_URL,
            'data' => '',
            'signature' => '',
            'language' => $language
        ];

        $order = $this->getOrder($orderId);
        if ($order && $order->getId()) {
            $paramsLiqPay = [
                'action' => 'pay',
                'amount' => $order->getGrandTotal(),
                'currency' => $order->getOrderCurrencyCode(),
                'description' => $this->helper->getLiqPayDescription($order),
                'order_id' => $order->getIncrementId(),
            ];

            $paramsLiqPay = $this->liqPay->prepareParams($paramsLiqPay);

            $paramsLiqPay = $this->liqPay->cnb_params($paramsLiqPay);

            $paramsLiqPay['result_url'] = $this->helper->getServerWebsiteUrl() . '/liqpay/checkout/thnks?language='.$language.'&orderNumber=' . $order->getIncrementId();

            $paramsLiqPay['server_url'] = $this->helper->getServerWebsiteUrl() . '/V1/liqpay/callback';

            $data = $this->liqPay->encode_params($paramsLiqPay);
            $signature = $this->liqPay->cnb_signature($paramsLiqPay);

            $formData = [
                'action' => $this->liqPay::DEFAULT_CHECKOUT_URL,
                'data' => $data,
                'signature' => $signature,
                'language' => $language
            ];
        }

        return $formData;
    }

    /**
     * @param $incrementId
     * @return OrderInterface|null
     */
    private function getOrder($incrementId): ?OrderInterface
    {
        try {
            $orderModel = $this->orderFactory->create();
            $order = $orderModel->load($incrementId);
            $orderId = $order->getId();

            if ($orderId) {
                return $order;
            }
        } catch (LocalizedException $exception) {

        }

        return null;
    }
}
