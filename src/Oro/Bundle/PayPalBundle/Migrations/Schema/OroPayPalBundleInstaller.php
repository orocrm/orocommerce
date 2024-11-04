<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroPayPalBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_2';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->updateOroIntegrationTransportTable($schema);

        $this->createOroPaypalCreditCardLblTable($schema);
        $this->createOroPaypalCreditCardShLblTable($schema);
        $this->createOroPaypalXprssChktLblTable($schema);
        $this->createOroPaypalXprssChktShrtLblTable($schema);

        $this->addOroPaypalCreditCardLblForeignKeys($schema);
        $this->addOroPaypalCreditCardShLblForeignKeys($schema);
        $this->addOroPaypalXprssChktLblForeignKeys($schema);
        $this->addOroPaypalXprssChktShrtLblForeignKeys($schema);
    }

    /**
     * Update oro_integration_transport table
     */
    private function updateOroIntegrationTransportTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('pp_express_checkout_action', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_credit_card_action', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_allowed_card_types', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('pp_express_checkout_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_partner', 'string', [
            'notnull' => false,
            'length' => 255,
            'comment' => '(DC2Type:crypted_string)'
        ]);
        $table->addColumn('pp_vendor', 'crypted_string', [
            'notnull' => false,
            'length' => 255,
            'comment' => '(DC2Type:crypted_string)'
        ]);
        $table->addColumn('pp_user', 'crypted_string', [
            'notnull' => false,
            'length' => 255,
            'comment' => '(DC2Type:crypted_string)'
        ]);
        $table->addColumn('pp_password', 'crypted_string', [
            'notnull' => false,
            'length' => 255,
            'comment' => '(DC2Type:crypted_string)'
        ]);
        $table->addColumn('pp_test_mode', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_debug_mode', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_require_cvv_entry', 'boolean', ['default' => '1', 'notnull' => false]);
        $table->addColumn('pp_zero_amount_authorization', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_auth_for_req_amount', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_use_proxy', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_proxy_host', 'crypted_string', [
            'notnull' => false,
            'length' => 255,
            'comment' => '(DC2Type:crypted_string)'
        ]);
        $table->addColumn('pp_proxy_port', 'crypted_string', [
            'notnull' => false,
            'length' => 255,
            'comment' => '(DC2Type:crypted_string)'
        ]);
        $table->addColumn('pp_enable_ssl_verification', 'boolean', ['default' => '1', 'notnull' => false]);
    }

    /**
     * Create oro_paypal_credit_card_lbl table
     */
    private function createOroPaypalCreditCardLblTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_paypal_credit_card_lbl');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_92E5B87EEB576E89');
    }

    /**
     * Create oro_paypal_credit_card_sh_lbl table
     */
    private function createOroPaypalCreditCardShLblTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_paypal_credit_card_sh_lbl');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_55FE472FEB576E89');
    }

    /**
     * Create oro_paypal_xprss_chkt_lbl table
     */
    private function createOroPaypalXprssChktLblTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_paypal_xprss_chkt_lbl');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_386D1FC6EB576E89');
    }

    /**
     * Create oro_paypal_xprss_chkt_shrt_lbl table
     */
    private function createOroPaypalXprssChktShrtLblTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_paypal_xprss_chkt_shrt_lbl');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_A9419ECEB576E89');
    }

    /**
     * Add oro_paypal_credit_card_lbl foreign keys.
     */
    private function addOroPaypalCreditCardLblForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_paypal_credit_card_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_paypal_credit_card_sh_lbl foreign keys.
     */
    private function addOroPaypalCreditCardShLblForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_paypal_credit_card_sh_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_paypal_xprss_chkt_lbl foreign keys.
     */
    private function addOroPaypalXprssChktLblForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_paypal_xprss_chkt_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_paypal_xprss_chkt_shrt_lbl foreign keys.
     */
    private function addOroPaypalXprssChktShrtLblForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_paypal_xprss_chkt_shrt_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
