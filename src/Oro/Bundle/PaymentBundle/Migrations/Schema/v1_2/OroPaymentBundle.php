<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPaymentBundle implements Migration, RenameExtensionAwareInterface
{
    use RenameExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updatePaymentTransactionTable($schema);
        $this->addConstraintsToPaymentTransactionTable($schema, $queries);
    }

    protected function updatePaymentTransactionTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_payment_transaction');
        $table->addColumn('frontend_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->getColumn('request')->setOptions(['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
        $table->getColumn('response')->setOptions(['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
    }

    protected function addConstraintsToPaymentTransactionTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_payment_transaction');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );

        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_payment_transaction',
            'oro_customer_user',
            ['frontend_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
