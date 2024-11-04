<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds organization id to the oro_inventory_level table
 */
class AddOrganization implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_inventory_level');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );

        // Update organization_id field with default organization (first one)
        $queries->addPostQuery('
            UPDATE oro_inventory_level
            SET organization_id = (SELECT id FROM oro_organization ORDER BY id ASC LIMIT 1)
            WHERE organization_id IS NULL
        ');
    }
}
