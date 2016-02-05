<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model;

use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;

class QuickAddRowTest extends \PHPUnit_Framework_TestCase
{
    const INDEX = 1;
    const SKU = 'SKU1';
    const QUANTITY = 1.00;

    public function testConstruct()
    {
        $row = new QuickAddRow(self::INDEX, self::SKU, self::QUANTITY);
        $this->assertEquals(self::INDEX, $row->getIndex());
        $this->assertEquals(self::SKU, $row->getSku());
        $this->assertEquals(self::QUANTITY, $row->getQuantity());
        $this->assertTrue($row->isComplete());
        $this->assertFalse($row->isValid());

        $row = new QuickAddRow(self::INDEX, self::SKU, null);
        $this->assertFalse($row->isComplete());
        $this->assertFalse($row->isValid());
    }
}
