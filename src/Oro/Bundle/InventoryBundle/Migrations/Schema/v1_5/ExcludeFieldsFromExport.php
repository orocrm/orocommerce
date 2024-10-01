<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Excludes fields from category export.
 */
class ExcludeFieldsFromExport implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        // Works in case when "availability_date" column does not yet exist.
        $table = $schema->getTable('oro_catalog_category');
        $table->changeColumn(
            'availability_date',
            [
                OroOptions::KEY => [
                    'importexport' => ['excluded' => true],
                ],
            ]
        );

        // Works in case when "availability_date" column already exists.
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                Category::class,
                'availability_date',
                'importexport',
                'excluded',
                true
            )
        );
    }
}
