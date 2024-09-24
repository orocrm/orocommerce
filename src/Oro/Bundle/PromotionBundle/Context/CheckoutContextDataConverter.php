<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

/**
 * Data converter that prepares promotion context data based on checkout entity to filter applicable promotions.
 */
class CheckoutContextDataConverter implements ContextDataConverterInterface
{
    /**
     * @var CheckoutToOrderConverter
     */
    private $checkoutToOrderConverter;

    /**
     * @var ContextDataConverterInterface
     */
    private $orderContextDataConverter;

    public function __construct(
        CheckoutToOrderConverter $checkoutToOrderConverter,
        ContextDataConverterInterface $orderContextDataConverter
    ) {
        $this->checkoutToOrderConverter = $checkoutToOrderConverter;
        $this->orderContextDataConverter = $orderContextDataConverter;
    }

    /**
     * @param Checkout $entity
     */
    #[\Override]
    public function getContextData($entity): array
    {
        if (!$this->supports($entity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Source entity "%s" is not supported.', get_class($entity))
            );
        }
        $order = $this->checkoutToOrderConverter->getOrder($entity);

        return $this->orderContextDataConverter->getContextData($order);
    }

    #[\Override]
    public function supports($entity): bool
    {
        return $entity instanceof Checkout && !$entity->getSourceEntity() instanceof QuoteDemand;
    }
}
