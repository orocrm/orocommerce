<?php

namespace Oro\Bundle\PromotionBundle\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;

/**
 * Represents data for Multi Shipping promotion.
 */
class MultiShippingPromotionData implements PromotionDataInterface, ShippingAwareInterface
{
    private PromotionDataInterface $promotionData;
    /** @var DiscountLineItem[] */
    private array $lineItems;

    /**
     * @param PromotionDataInterface $promotionData
     * @param DiscountLineItem[]     $lineItems
     */
    public function __construct(PromotionDataInterface $promotionData, array $lineItems)
    {
        $this->promotionData = $promotionData;
        $this->lineItems = $lineItems;
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->promotionData->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountConfiguration()
    {
        return $this->promotionData->getDiscountConfiguration();
    }

    /**
     * {@inheritDoc}
     */
    public function isUseCoupons()
    {
        return $this->promotionData->isUseCoupons();
    }

    /**
     * {@inheritDoc}
     */
    public function getCoupons()
    {
        return $this->promotionData->getCoupons();
    }

    /**
     * {@inheritDoc}
     */
    public function getProductsSegment()
    {
        return $this->promotionData->getProductsSegment();
    }

    /**
     * {@inheritDoc}
     */
    public function getRule()
    {
        return $this->promotionData->getRule();
    }

    /**
     * {@inheritDoc}
     */
    public function getSchedules()
    {
        return $this->promotionData->getSchedules();
    }

    /**
     * {@inheritDoc}
     */
    public function getScopes()
    {
        return $this->promotionData->getScopes();
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingCost()
    {
        $amount = 0.0;
        $currency = null;
        foreach ($this->lineItems as $lineItem) {
            $sourceLineItem = $lineItem->getSourceLineItem();
            if (!$sourceLineItem instanceof ShippingAwareInterface) {
                continue;
            }
            $lineItemShippingCost = $sourceLineItem->getShippingCost();
            if (null === $lineItemShippingCost) {
                continue;
            }

            $amount += $lineItemShippingCost->getValue();
            if (null === $currency) {
                $currency = $lineItemShippingCost->getCurrency();
            }
        }

        return null !== $currency
            ? Price::create($amount, $currency)
            : null;
    }

    /**
     * @return DiscountLineItem[]
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }
}
