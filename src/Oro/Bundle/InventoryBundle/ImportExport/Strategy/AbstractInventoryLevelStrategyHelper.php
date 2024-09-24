<?php

namespace Oro\Bundle\InventoryBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A base helper for InventoryLevel import strategy.
 */
abstract class AbstractInventoryLevelStrategyHelper implements InventoryLevelStrategyHelperInterface
{
    /** @var  DatabaseHelper $databaseHelper */
    protected $databaseHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var  InventoryLevelStrategyHelperInterface $successor */
    protected $successor;

    /** @var array $errors */
    protected $errors = [];

    public function __construct(DatabaseHelper $databaseHelper, TranslatorInterface $translator)
    {
        $this->databaseHelper = $databaseHelper;
        $this->translator = $translator;
    }

    /**
     * Using DatabaseHelper we search for an entity using its class name and
     * a criteria composed of a field from this entity and its value.
     * If entity is not found then add a validation error on the context.
     *
     * @param string $class
     * @param array $criteria
     * @param null|string $alternaiveClassName
     * @return null|object
     */
    protected function checkAndRetrieveEntity($class, array $criteria = [], $alternaiveClassName = null)
    {
        $existingEntity = $this->databaseHelper->findOneBy($class, $criteria);
        if (!$existingEntity) {
            $classNamespace = explode('\\', $class);
            $shortClassName = end($classNamespace);
            $this->addError(
                'oro.inventory.import.error.not_found_entity',
                ['%entity%' => $alternaiveClassName ?: $shortClassName]
            );
        }

        return $existingEntity;
    }

    /**
     * Translates the received error and adds it to the list of errors
     */
    protected function addError(string $error, array $translationParams = [], string $prefix = null)
    {
        $errorMessage = $this->translator->trans($error, $translationParams);

        if ($prefix) {
            $prefix = $this->translator->trans($prefix);
        }

        $this->errors[$errorMessage] = $prefix;
    }

    #[\Override]
    public function getErrors($deep = false)
    {
        $successorErrors = $this->successor ? $this->successor->getErrors(true) : [];

        return array_merge($this->errors, $successorErrors);
    }

    #[\Override]
    public function setSuccessor(InventoryLevelStrategyHelperInterface $successor)
    {
        $this->successor = $successor;
    }

    /**
     * Helper function which extracts an entity from an array based on a key.
     * @param array $entities
     * @param string $name
     * @return null
     */
    protected function getProcessedEntity($entities, $name)
    {
        return isset($entities[$name]) ? $entities[$name] : null;
    }

    #[\Override]
    public function clearCache($deep = false)
    {
        $this->errors = [];

        if ($deep && $this->successor) {
            $this->successor->clearCache($deep);
        }
    }
}
