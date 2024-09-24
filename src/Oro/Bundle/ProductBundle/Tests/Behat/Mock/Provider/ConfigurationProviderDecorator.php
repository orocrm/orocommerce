<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Mock\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;

class ConfigurationProviderDecorator implements ConfigurationProviderInterface
{
    /** @var ConfigurationProviderInterface */
    private $configurationProvider;

    public function __construct(ConfigurationProviderInterface $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    #[\Override]
    public function getConfiguration(string $gridName): DatagridConfiguration
    {
        $configuration = $this->configurationProvider->getConfiguration($gridName);

        if ($gridName == 'frontend-product-search-grid') {
            $configuration->offsetAddToArray(
                'options',
                [
                    'noDataMessages' => [
                        'emptyGrid' => 'oro.product.datagrid.empty_grid',
                        'emptyFilteredGrid' => 'oro.product.datagrid.empty_filtered_grid'
                    ]
                ]
            );
        }

        return $configuration;
    }

    #[\Override]
    public function isApplicable(string $gridName): bool
    {
        return $this->configurationProvider->isApplicable($gridName);
    }

    #[\Override]
    public function isValidConfiguration(string $gridName): bool
    {
        try {
            $this->configurationProvider->getConfiguration($gridName);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }
}
