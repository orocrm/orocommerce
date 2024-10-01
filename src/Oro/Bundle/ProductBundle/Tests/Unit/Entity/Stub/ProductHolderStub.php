<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\ProductBundle\Entity\Product as ProductEntity;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

class ProductHolderStub implements ProductHolderInterface
{
    /** @var ProductEntity */
    private $product;

    public function __construct(ProductEntity $product)
    {
        $this->product = $product;
    }

    #[\Override]
    public function getEntityIdentifier()
    {
        return $this->product->getId();
    }

    #[\Override]
    public function getProduct()
    {
        return $this->product;
    }

    #[\Override]
    public function getProductSku()
    {
        return $this->getProduct()->getSku();
    }
}
