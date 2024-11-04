<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateRelatedProductsTable implements
    Migration,
    RenameExtensionAwareInterface,
    OrderedMigrationInterface
{
    use RenameExtensionAwareTrait;
    use MigrationConstraintTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_product_related_products');

        $table->removeForeignKey($this->getConstraintName($table, "related_product_id"));
        $table->removeForeignKey($this->getConstraintName($table, "product_id"));
        $table->dropIndex('idx_oro_product_related_products_related_product_id');
        $table->dropIndex('idx_oro_product_related_products_unique');

        $this->renameExtension->renameColumn($schema, $queries, $table, 'related_product_id', 'related_item_id');
    }

    #[\Override]
    public function getOrder()
    {
        return 1;
    }
}
