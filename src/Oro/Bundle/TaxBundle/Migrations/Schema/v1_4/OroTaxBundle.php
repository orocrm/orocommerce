<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTaxBundle implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    use RenameExtensionAwareTrait;
    use MigrationConstraintTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->rename($schema, $queries);
    }

    private function rename(Schema $schema, QueryBag $queries)
    {
        // account group
        $extension = $this->renameExtension;

        $table = $schema->getTable('oro_tax_acc_grp_tc_acc_grp');
        $fk = $this->getConstraintName($table, 'account_group_id');
        $table->removeForeignKey($fk);

        $fk = $this->getConstraintName($table, 'account_group_tax_code_id');
        $table->removeForeignKey($fk);

        $extension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_group_id',
            'customer_group_id'
        );
        $extension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_group_tax_code_id',
            'customer_group_tax_code_id'
        );
        $extension->renameTable($schema, $queries, 'oro_tax_acc_grp_tc_acc_grp', 'oro_tax_cus_grp_tc_cus_grp');

        // account
        // drop fk
        $table = $schema->getTable('oro_tax_acc_tax_code_acc');
        $table->removeForeignKey($this->getConstraintName($table, 'account_tax_code_id'));
        $table->removeForeignKey($this->getConstraintName($table, 'account_id'));
        $table->dropIndex('UNIQ_53167F2A9B6B5FBA');

        $table = $schema->getTable('oro_tax_rule');
        $table->removeForeignKey($this->getConstraintName($table, 'account_tax_code_id'));

        // rename
        $table = $schema->getTable('oro_tax_acc_tax_code_acc');
        $extension->renameColumn($schema, $queries, $table, 'account_tax_code_id', 'customer_tax_code_id');
        $extension->renameColumn($schema, $queries, $table, 'account_id', 'customer_id');
        $extension->renameTable($schema, $queries, 'oro_tax_acc_tax_code_acc', 'oro_tax_cus_tax_code_cus');

        $table = $schema->getTable('oro_tax_rule');
        $extension->renameColumn($schema, $queries, $table, 'account_tax_code_id', 'customer_tax_code_id');

        $extension->renameTable($schema, $queries, 'oro_tax_account_tax_code', 'oro_tax_customer_tax_code');
    }

    #[\Override]
    public function getOrder()
    {
        return 1;
    }
}
