<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductLineItemTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    private const IDENTIFIER = 'identifier';
    private const UNIT_CODE = 'unit_code';
    private const UNIT_CODE2 = 'unit_code2';
    private const PRODUCT_SKU = 'product_sku';

    public function testProperties()
    {
        $unit = new ProductUnit();
        $unit->setCode(self::UNIT_CODE);
        $unit2 = new ProductUnit();
        $unit2->setCode(self::UNIT_CODE2);
        $product = new Product();
        $product->setSku(self::PRODUCT_SKU);
        $properties = [
            ['unit', $unit],
            ['quantity', 5],
            ['product', $product],
        ];
        $lineItem = new ProductLineItem(self::IDENTIFIER);
        $this->assertPropertyAccessors($lineItem, $properties);
        $this->assertEquals(self::IDENTIFIER, $lineItem->getEntityIdentifier());
        $this->assertEquals($lineItem, $lineItem->getProductHolder());

        $lineItem->setUnit($unit);
        $this->assertEquals(self::UNIT_CODE, $lineItem->getProductUnitCode());

        $lineItem->setProductUnit($unit2);
        $this->assertEquals(self::UNIT_CODE2, $lineItem->getProductUnitCode());

        $lineItem->setProduct($product);
        $this->assertEquals(self::PRODUCT_SKU, $lineItem->getProductSku());
        $this->assertNull($lineItem->getParentProduct());
    }
}
