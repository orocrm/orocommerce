<?php

namespace Oro\Bundle\ProductBundle\Service;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;

class SingleUnitModeService implements SingleUnitModeServiceInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function isSingleUnitMode()
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::SINGLE_UNIT_MODE));
    }

    #[\Override]
    public function isSingleUnitModeCodeVisible()
    {
        if (!$this->isSingleUnitMode()) {
            return true;
        }
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::SINGLE_UNIT_MODE_SHOW_CODE));
    }

    #[\Override]
    public function getDefaultUnitCode()
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_UNIT));
    }
}
