<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateEntityConfigExtendClassQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $fromExtendClass;

    /**
     * @var string
     */
    protected $toExtendClass;

    public function __construct($entityName, $fromExtendClass, $toExtendClass)
    {
        $this->entityName = $entityName;
        $this->fromExtendClass = $fromExtendClass;
        $this->toExtendClass = $toExtendClass;
    }

    #[\Override]
    public function getDescription()
    {
        return 'Update entity extend class configuration on given entity';
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->updateEntityConfig($logger);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function updateEntityConfig(LoggerInterface $logger)
    {
        $sql = 'SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1';
        $parameters = [$this->entityName];
        $row = $this->connection->fetchAssociative($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        $id = $row['id'];
        $data = isset($row['data']) ? $this->connection->convertToPHPValue($row['data'], Types::ARRAY) : [];

        $data['extend']['schema']['entity'] = $this->toExtendClass;

        $extendConfig = $data['extend']['schema']['doctrine'][$this->fromExtendClass];
        unset($data['extend']['schema']['doctrine'][$this->fromExtendClass]);
        $data['extend']['schema']['doctrine'][$this->toExtendClass] = $extendConfig;

        $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

        $sql = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
        $parameters = [$data, $id];
        $statement = $this->connection->prepare($sql);
        $statement->executeQuery($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }
}
