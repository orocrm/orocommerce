<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_1;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class InsertSelectPriceListRelationTablesQuery extends ParametrizedMigrationQuery
{
    const DEFAULT_PRIORITY = 100;

    /**
     * @var int
     */
    protected static $defaultWebsiteId;

    /**
     * @var string
     */
    protected $oldTableName;

    /**
     * @var string
     */
    protected $newTableName;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @param string $newTableName
     * @param string $oldTableName
     * @param string $fieldName
     */
    public function __construct($newTableName, $oldTableName, $fieldName)
    {
        $this->newTableName = $newTableName;
        $this->oldTableName = $oldTableName;
        $this->fieldName = $fieldName;
    }

    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->migrateData($logger, true);
        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->migrateData($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    public function migrateData(LoggerInterface $logger, $dryRun = false)
    {
        $fields = ['price_list_id'];
        $websiteId = 'website_id';
        if ($this->fieldName !== 'website_id') {
            $fields[] = $this->fieldName;
            $websiteId = $this->getDefaultWebsiteId();
        }

        $insertFields = implode(', ', array_merge($fields, ['website_id', 'priority']));
        $selectFields = implode(', ', array_merge($fields, [$websiteId, static::DEFAULT_PRIORITY]));

        $sql = sprintf(
            'INSERT INTO %s (%s) SELECT %s FROM %s',
            $this->newTableName,
            $insertFields,
            $selectFields,
            $this->oldTableName
        );

        $this->logQuery($logger, $sql);
        if (!$dryRun) {
            $this->connection->executeStatement($sql);
        }
    }

    /**
     * @return \Oro\Bundle\WebsiteBundle\Entity\Website
     */
    protected function getDefaultWebsiteId()
    {
        if (!static::$defaultWebsiteId) {
            static::$defaultWebsiteId = $this->connection->createQueryBuilder()
                ->select('id')
                ->from('orob2b_website')
                ->orderBy('id', Criteria::ASC)
                ->setMaxResults(1)
                ->execute()->fetchOne();
        }

        return static::$defaultWebsiteId;
    }
}
