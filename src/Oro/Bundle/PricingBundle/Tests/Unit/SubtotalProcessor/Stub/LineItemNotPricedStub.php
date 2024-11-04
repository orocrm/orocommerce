<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemChecksumAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

class LineItemNotPricedStub implements
    ProductLineItemInterface,
    ProductLineItemChecksumAwareInterface,
    ProductKitItemLineItemsAwareInterface
{
    private ?float $quantity = null;

    private ?Product $product = null;

    private ?ProductUnit $unit = null;

    private Collection $kitItemLineItems;

    private string $checksum = '';

    public function __construct()
    {
        $this->kitItemLineItems = new ArrayCollection();
    }

    /**
     * @param float $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return float
     */
    #[\Override]
    public function getQuantity()
    {
        return $this->quantity;
    }

    #[\Override]
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    #[\Override]
    public function getProductSku()
    {
    }

    #[\Override]
    public function getEntityIdentifier()
    {
    }

    #[\Override]
    public function getProductHolder()
    {
    }

    #[\Override]
    public function getProductUnit()
    {
        return $this->unit;
    }

    public function setProductUnit($unit)
    {
        $this->unit = $unit;
    }

    #[\Override]
    public function getProductUnitCode()
    {
    }

    /**
     * @return Collection<ProductKitItemLineItemInterface>
     */
    #[\Override]
    public function getKitItemLineItems(): Collection
    {
        return $this->kitItemLineItems;
    }

    public function addKitItemLineItem(ProductKitItemLineItemInterface $productKitItemLineItem): self
    {
        if (!$this->kitItemLineItems->contains($productKitItemLineItem)) {
            $productKitItemLineItem->setLineItem($this);
            $this->kitItemLineItems->add($productKitItemLineItem);
        }

        return $this;
    }

    public function removeKitItemLineItem(ProductKitItemLineItemInterface $productKitItemLineItem): self
    {
        $this->kitItemLineItems->removeElement($productKitItemLineItem);

        return $this;
    }

    #[\Override]
    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;
        return $this;
    }

    #[\Override]
    public function getParentProduct()
    {
        return null;
    }
}
