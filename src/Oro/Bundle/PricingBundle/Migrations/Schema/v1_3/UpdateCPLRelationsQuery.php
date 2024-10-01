<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_3;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateCPLRelationsQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @param string $className
     */
    public function __construct($className)
    {
        $this->tableName = $className;
    }

    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->update($this->tableName)
            ->set('full_combined_price_list_id', 'combined_price_list_id')
        ;

        $this->logQuery($logger, $qb->getSql());
        if (!$dryRun) {
            $qb->execute();
        }
    }
}
