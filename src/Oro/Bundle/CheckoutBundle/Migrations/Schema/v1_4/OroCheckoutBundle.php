<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCheckoutBundle implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    use RenameExtensionAwareTrait;
    use MigrationConstraintTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateCheckoutTable($schema, $queries);
    }

    protected function updateCheckoutTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_checkout');
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

    /**
     * Get the order of this migration
     *
     * @return integer
     */
    #[\Override]
    public function getOrder()
    {
        return 1;
    }
}
