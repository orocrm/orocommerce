<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Frontend;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

/**
 * Adds information required to display inventory status to storefront product grid.
 */
class ProductDatagridInventoryStatusListener
{
    private const string SELECT_PATH = '[source][query][select]';

    public function __construct(protected EnumOptionsProvider $enumOptionsProvider)
    {
    }

    public function onPreBuild(PreBuild $event): void
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(self::SELECT_PATH, ['text.inv_status as inventory_status']);

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                'inventory_status' => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_STRING,
                ],
                'inventory_status_label' => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_STRING,
                ],
            ]
        );
    }

    public function onResultAfter(SearchResultAfter $event): void
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $inventoryStatuses = array_flip(
            $this->enumOptionsProvider->getEnumChoicesByCode(Product::INVENTORY_STATUS_ENUM_CODE)
        );
        foreach ($records as $record) {
            $inventoryStatus = $record->getValue('inventory_status');
            $record->setValue('inventory_status_label', $inventoryStatuses[$inventoryStatus] ?? $inventoryStatus);
        }
    }
}
