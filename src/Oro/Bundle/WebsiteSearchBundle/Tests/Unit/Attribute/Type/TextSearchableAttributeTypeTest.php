<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\TextSearchableAttributeType;

class TextSearchableAttributeTypeTest extends SearchableAttributeTypeTestCase
{
    #[\Override]
    protected function getSearchableAttributeTypeClassName()
    {
        return TextSearchableAttributeType::class;
    }

    public function testGetFilterStorageFieldTypes()
    {
        $this->assertSame(
            [SearchAttributeTypeInterface::VALUE_MAIN => Query::TYPE_TEXT],
            $this->getSearchableAttributeType()->getFilterStorageFieldTypes($this->attribute)
        );
    }

    public function testGetSorterStorageFieldType()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getSearchableAttributeType()->getSorterStorageFieldType($this->attribute);
    }

    public function testGetFilterType()
    {
        $this->assertSame(
            SearchAttributeTypeInterface::FILTER_TYPE_STRING,
            $this->getSearchableAttributeType()->getFilterType($this->attribute)
        );
    }

    public function testIsLocalizable()
    {
        $this->assertFalse($this->getSearchableAttributeType()->isLocalizable($this->attribute));
    }

    public function testGetFilterableFieldNames()
    {
        $this->assertSame(
            [SearchAttributeTypeInterface::VALUE_MAIN => self::FIELD_NAME],
            $this->getSearchableAttributeType()->getFilterableFieldNames($this->attribute)
        );
    }

    public function testGetSortableFieldNameException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getSearchableAttributeType()->getSortableFieldName($this->attribute);
    }

    public function testGetSearchableFieldName()
    {
        $this->assertSame(
            self::FIELD_NAME,
            $this->getSearchableAttributeType()->getSearchableFieldName($this->attribute)
        );
    }
}
