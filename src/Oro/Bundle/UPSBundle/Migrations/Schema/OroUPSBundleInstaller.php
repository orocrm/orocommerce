<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UPSBundle\Migrations\Schema\v1_2\UpdatePasswordMigrationQuery;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroUPSBundleInstaller implements Installation, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_4';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->updateOroIntegrationTransportTable($schema);

        $this->createOroUPSShippingServiceTable($schema);
        $this->createOroUPSTransportShipServiceTable($schema);
        $this->createOroUPSTransportLabelTable($schema);

        $this->addOroUpsTransportShipServiceForeignKeys($schema);
        $this->addOroUpsTransportLabelForeignKeys($schema);
        $this->addOroIntegrationTransportForeignKeys($schema);
        $this->addOroUPSShippingServiceForeignKeys($schema);

        $queries->addQuery(new UpdatePasswordMigrationQuery($this->container));
    }

    private function updateOroIntegrationTransportTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('ups_test_mode', 'boolean', ['notnull' => false, 'default' => false]);
        $table->addColumn('ups_api_user', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_api_password', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_api_key', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_client_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_client_secret', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_access_token', 'text', ['notnull' => false]);
        $table->addColumn(
            'ups_access_token_expires',
            'datetime',
            ['notnull' => false, 'comment' => '(DC2Type:datetime)']
        );
        $table->addColumn('ups_shipping_account_number', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('ups_shipping_account_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_pickup_type', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('ups_unit_of_weight', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('ups_country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('ups_invalidate_cache_at', 'datetime', [
            'notnull' => false,
            'comment' => '(DC2Type:datetime)'
        ]);
    }

    private function createOroUPSShippingServiceTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_ups_shipping_service');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['notnull' => true, 'length' => 10]);
        $table->addColumn('description', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('country_code', 'string', ['length' => 2]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['country_code'], 'IDX_C6DD8778F026BB7C');
    }

    private function createOroUPSTransportShipServiceTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_ups_transport_ship_service');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('ship_service_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'ship_service_id']);
        $table->addIndex(['transport_id'], 'IDX_1554DDE9909C13F');
        $table->addIndex(['ship_service_id'], 'IDX_1554DDE37CA9B1D');
    }

    private function createOroUPSTransportLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_ups_transport_label');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'IDX_1554DDE9909C13D');
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_1554DDE37CA9B1F');
    }

    private function addOroIntegrationTransportForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['ups_country_code'],
            ['iso2_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    private function addOroUPSShippingServiceForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_ups_shipping_service');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    private function addOroUpsTransportShipServiceForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_ups_transport_ship_service');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_ups_shipping_service'),
            ['ship_service_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addOroUpsTransportLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_ups_transport_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
