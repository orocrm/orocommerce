<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateFallbackEntitySystemOptionConfig extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $systemOption;

    public function __construct($entityName, $fieldName, $systemOption)
    {
        $this->entityName = $entityName;
        $this->fieldName = $fieldName;
        $this->systemOption = $systemOption;
    }

    #[\Override]
    public function getDescription()
    {
        return 'Update system option config for fallback entity field config';
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
        $sql        = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';
        $parameters = [$this->entityName, $this->fieldName];
        $row        = $this->connection->fetchAssociative($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        $id = $row['id'];
        $data = isset($row['data']) ? $this->connection->convertToPHPValue($row['data'], Types::ARRAY) : [];

        $data['fallback']['fallbackList']['systemConfig']['configName'] = $this->systemOption;

        $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

        $sql        = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
        $parameters = [$data, $id];
        $statement = $this->connection->prepare($sql);
        $statement->executeQuery($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }
}
