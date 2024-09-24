<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Migrate ID from integer to UUID to prevent reaching max integer value.
 */
class WebCatalogProductLimitationIdToUUID implements Migration, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_web_catalog_product_limit');

        // Column type already changed
        if ($table->getColumn('id')->getType()->getName() !== Types::INTEGER) {
            return;
        }

        if (DatabasePlatformInterface::DATABASE_POSTGRESQL === $this->connection->getDatabasePlatform()->getName()) {
            $queries->addQuery('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
            $queries->addQuery('ALTER TABLE oro_web_catalog_product_limit DROP COLUMN id;');
            $queries->addQuery('ALTER TABLE oro_web_catalog_product_limit ADD COLUMN id UUID;');
            $queries->addQuery('UPDATE oro_web_catalog_product_limit SET id=uuid_generate_v4();');
            $queries->addQuery('ALTER TABLE oro_web_catalog_product_limit ADD PRIMARY KEY (id);');
        } else {
            $table->dropPrimaryKey();
            $table->changeColumn(
                'id',
                [
                    'type' => Type::getType("guid"),
                    'notnull' => false,
                    'comment' => '(DC2Type:guid)'
                ]
            );
            $queries->addQuery('UPDATE oro_web_catalog_product_limit SET id=uuid()');
            $queries->addQuery(
                "ALTER TABLE oro_web_catalog_product_limit CHANGE id id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)'"
            );
            $table->setPrimaryKey(['id']);
        }
    }
}
