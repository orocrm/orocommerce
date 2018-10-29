<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Oro\Bundle\FrontendBundle\Tests\Behat\Element\Grid;

class FrontendProductGrid extends Grid
{
    const DEFAULT_MAPPINGS = [
        'GridRow' => 'ProductFrontendGridRow',
        'GridTable' => 'ProductFrontendGridTable',
        'GridToolbarPaginator' => 'FrontendGridToolbarPaginator',
        'MassActionHeadCheckbox' => 'ProductFrontendMassActionHeadCheckbox',
        'MassActionButton' => 'ProductFrontendMassActionButton',
        'GridFloatingMenu' => 'ProductFrontendGridFloatingMenu',
        'GridColumnManager' => 'FrontendGridColumnManager',
        'GridFilterManager' => 'FrontendGridFilterManager',
    ];

    /**
     * {@inheritdoc}
     */
    public function getRows()
    {
        return $this->getElements($this->getMappedChildElementName(static::TABLE_ROW_ELEMENT));
    }
}
