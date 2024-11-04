<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Frontend\DataConverter;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Event\ProductExportDataConverterEvent;

/**
 * Prepare product data for export.
 */
class ProductExportDataConverter extends PropertyPathTitleDataConverter
{
    public const PRODUCT_NAME_FIELD = 'names';

    protected ConfigProvider $configProvider;

    public function setConfigProvider(ConfigProvider $configProvider): void
    {
        $this->configProvider = $configProvider;
    }

    #[\Override]
    protected function isFieldAvailableForExport(string $entityName, string $fieldName): bool
    {
        if (!is_a($entityName, Product::class, true)) {
            return parent::isFieldAvailableForExport($entityName, $fieldName);
        }

        if ($fieldName === self::PRODUCT_NAME_FIELD) {
            // Name field should always be present in export.
            return true;
        }

        $config = $this->configProvider->getConfig($entityName, $fieldName);

        return $config->has('use_in_export') ? $config->get('use_in_export', false, false) : false;
    }

    protected function getHeadersAndRulesForCustomAttributes(array $rules, array $backendHeaders): array
    {
        if ($this->dispatcher) {
            $event = new ProductExportDataConverterEvent($rules, $backendHeaders);
            $this->dispatcher->dispatch(
                $event,
                ProductExportDataConverterEvent::FRONTEND_PRODUCT_CONVERT_TO_EXPORT_DATA
            );
            [$rules, $backendHeaders] = [$event->getHeaderRules(), $event->getBackendHeaders()];
        }

        return [$rules, $backendHeaders];
    }

    #[\Override]
    protected function getRelatedEntityRulesAndBackendHeaders(
        $entityName,
        $singleRelationDeepLevel,
        $multipleRelationDeepLevel,
        $field,
        $fieldHeader,
        $fieldOrder
    ) {
        if ($field['name'] === self::PRODUCT_NAME_FIELD && is_a($entityName, Product::class, true)) {
            // Adds the rule and header for the "name" column.

            return [
                [$fieldHeader => ['value' => self::PRODUCT_NAME_FIELD, 'order' => $fieldOrder]],
                [['value' => self::PRODUCT_NAME_FIELD, 'order' => $fieldOrder]],
            ];
        }

        return parent::getRelatedEntityRulesAndBackendHeaders(
            $entityName,
            $singleRelationDeepLevel,
            $multipleRelationDeepLevel,
            $field,
            $fieldHeader,
            $fieldOrder
        );
    }

    #[\Override]
    protected function getRelatedEntityRules(
        $entityName,
        $singleRelationDeepLevel,
        $multipleRelationDeepLevel,
        $field,
        $fieldHeader,
        $fieldOrder
    ) {
        if ($field['name'] === self::PRODUCT_NAME_FIELD && is_a($entityName, Product::class, true)) {
            // Adds the rule for the "name" column.
            return [$fieldHeader => ['value' => self::PRODUCT_NAME_FIELD, 'order' => $fieldOrder]];
        }

        return parent::getRelatedEntityRules(
            $entityName,
            $singleRelationDeepLevel,
            $multipleRelationDeepLevel,
            $field,
            $fieldHeader,
            $fieldOrder
        );
    }

    #[\Override]
    protected function getEntityRulesAndBackendHeaders(
        $entityName,
        $fullData = false,
        $singleRelationDeepLevel = 0,
        $multipleRelationDeepLevel = 0
    ) {
        [$rules, $backendHeaders] = parent::getEntityRulesAndBackendHeaders(
            $entityName,
            $fullData,
            $singleRelationDeepLevel,
            $multipleRelationDeepLevel
        );

        if (is_a($entityName, Product::class, true)) {
            [$rules, $backendHeaders] = $this->getHeadersAndRulesForCustomAttributes($rules, $backendHeaders);
        }

        return [$rules, $backendHeaders];
    }

    #[\Override]
    protected function getFieldHeader($entityName, $field): string
    {
        if (!is_array($field) || !array_key_exists('name', $field)) {
            throw new \InvalidArgumentException('Property is not array or key "name" is not exist.');
        }

        return $this->fieldHelper->getConfigValue($entityName, $field['name'], 'header', $field['label']);
    }
}
