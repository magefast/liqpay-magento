<?php

namespace LiqpayMagento\LiqPay\Model\Resolver;

use LiqpayMagento\LiqPay\Service\PaymentFormLiqPay;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Psr\Log\LoggerInterface;

/**
 * Customers field resolver, used for GraphQL request processing.
 */
class PaymentFormRedirect implements ResolverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PaymentFormLiqPay
     */
    private PaymentFormLiqPay $paymentFormLiqPay;

    /**
     * @param PaymentFormLiqPay $paymentFormLiqPay
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaymentFormLiqPay $paymentFormLiqPay,
        LoggerInterface   $logger
    )
    {
        $this->paymentFormLiqPay = $paymentFormLiqPay;
        $this->logger = $logger;
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        if (empty($args['id'])) {
            throw new GraphQlInputException(__('Required parameter "id" is missing'));
        }

        $orderId = $args['id'];
        $formData = $this->paymentFormLiqPay->execute($orderId);

        try {
            return [
                'action' => $formData['action'],
                'data' => $formData['data'],
                'signature' => $formData['signature'],
                'language' => $formData['language']
            ];
        } catch (NoSuchEntityException|LocalizedException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        }
    }
}
