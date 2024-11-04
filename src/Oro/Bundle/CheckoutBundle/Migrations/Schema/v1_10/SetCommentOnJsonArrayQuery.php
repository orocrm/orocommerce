<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Platforms\PostgreSQL92Platform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Add comment to oro_checkout.completed_data field on Postgres
 */
class SetCommentOnJsonArrayQuery extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info(
            'Set missing doctrine type hint comment on json_array field.'
        );
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof PostgreSQL92Platform) {
            $commentSql = "COMMENT ON COLUMN oro_checkout.completed_data IS '(DC2Type:json_array)'";
            $this->logQuery($logger, $commentSql);

            if (!$dryRun) {
                $this->connection->executeStatement($commentSql);
            }
        }
    }
}
