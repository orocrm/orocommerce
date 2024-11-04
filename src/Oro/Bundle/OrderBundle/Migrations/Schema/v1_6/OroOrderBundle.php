<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrderBundle implements Migration
{
    /**
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroOrderShippingTrackingTable($schema);
        $this->addOroOrderShippingTrackingForeignKeys($schema);
    }

    /**
     * Create oro_order_shipping_tracking table
     */
    protected function createOroOrderShippingTrackingTable(Schema $schema)
    {
        $table = $schema->createTable('oro_order_shipping_tracking');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('order_id', 'integer', ['notnull' => true]);
        $table->addColumn('method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('number', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_order_shipping_tracking foreign keys.
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOroOrderShippingTrackingForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_order_shipping_tracking');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
