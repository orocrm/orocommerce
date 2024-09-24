<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrderBundle implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    use MigrationConstraintTrait;
    use RenameExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_order');
        $table->removeForeignKey($this->getConstraintName($table, 'account_user_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_user_id',
            'customer_user_id'
        );
        $table->removeForeignKey($this->getConstraintName($table, 'account_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_id',
            'customer_id'
        );
    }

    #[\Override]
    public function getOrder()
    {
        return 1;
    }
}
