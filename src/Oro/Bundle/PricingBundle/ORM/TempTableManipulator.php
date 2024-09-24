<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Manipulate temp tables created by a given entity table.
 */
class TempTableManipulator implements TempTableManipulatorInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var TempTableManipulatorInterface
     */
    private $postgreSqlManipulator;

    /**
     * @var TempTableManipulatorInterface
     */
    private $mySqlManipulator;

    public function __construct(
        ManagerRegistry $registry,
        TempTableManipulatorInterface $postgreSqlManipulator,
        TempTableManipulatorInterface $mySqlManipulator
    ) {
        $this->registry = $registry;
        $this->postgreSqlManipulator = $postgreSqlManipulator;
        $this->mySqlManipulator = $mySqlManipulator;
    }

    #[\Override]
    public function createTempTableForEntity(string $className, $identifier)
    {
        $this->getManipulator()->createTempTableForEntity($className, $identifier);
    }

    #[\Override]
    public function dropTempTableForEntity(string $className, $identifier)
    {
        $this->getManipulator()->dropTempTableForEntity($className, $identifier);
    }

    #[\Override]
    public function truncateTempTableForEntity(string $className, $identifier)
    {
        $this->getManipulator()->truncateTempTableForEntity($className, $identifier);
    }

    #[\Override]
    public function copyDataFromTemplateTableToEntityTable(string $className, $identifier, array $fields)
    {
        $this->getManipulator()->copyDataFromTemplateTableToEntityTable($className, $identifier, $fields);
    }

    #[\Override]
    public function moveDataFromTemplateTableToEntityTable(string $className, $identifier, array $fields)
    {
        $this->getManipulator()->moveDataFromTemplateTableToEntityTable($className, $identifier, $fields);
    }

    #[\Override]
    public function getTempTableNameForEntity(string $className, $identifier): string
    {
        return $this->getManipulator()->getTempTableNameForEntity($className, $identifier);
    }

    #[\Override]
    public function getTableNameForEntity(string $className): string
    {
        return $this->getManipulator()->getTableNameForEntity($className);
    }

    #[\Override]
    public function setInsertSelectExecutor(ShardQueryExecutorNativeSqlInterface $queryExecutor)
    {
        $this->getManipulator()->setInsertSelectExecutor($queryExecutor);
    }

    #[\Override]
    public function insertData(
        string $insertToTableName,
        string $className,
        $identifier,
        array $fields,
        QueryBuilder $qb,
        bool $applyOnDuplicateKeyUpdate = true,
        ?array $tempTableAliases = []
    ) {
        $this->getManipulator()->insertData(
            $insertToTableName,
            $className,
            $identifier,
            $fields,
            $qb,
            $applyOnDuplicateKeyUpdate,
            $tempTableAliases
        );
    }

    private function getManipulator(): TempTableManipulatorInterface
    {
        if ($this->registry->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            return $this->postgreSqlManipulator;
        }

        return $this->mySqlManipulator;
    }
}
