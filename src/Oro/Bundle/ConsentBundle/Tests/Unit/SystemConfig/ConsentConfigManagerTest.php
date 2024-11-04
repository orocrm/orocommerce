<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\SystemConfig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsentConfigManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $globalConfig;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConsentConfigConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $converter;

    /** @var ConsentConfigManager */
    private $consentConfigManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->globalConfig = $this->createMock(ConfigManager::class);
        $this->converter = $this->createMock(ConsentConfigConverter::class);

        $this->consentConfigManager = new ConsentConfigManager(
            $this->configManager,
            $this->globalConfig,
            $this->converter
        );
    }

    public function configProvider(): array
    {
        return [
            'config for website with use parent' => [
                'config' => [
                    'value' => [
                        ['consent' => 1, 'sort_order' => 1]
                    ],
                    ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => true
                ],
                'convertedConfig' => [
                    new ConsentConfig($this->createConsent(1))
                ],
                'consent' => $this->createConsent(1),
                'website' => $this->createWebsite(1)
            ],
            'config for website must be updated' => [
                'config' => [
                    'value' => [
                        ['consent' => 1, 'sort_order' => 1],
                        ['consent' => 2, 'sort_order' => 2]
                    ],
                    ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => false
                ],
                'convertedConfig' => [
                    new ConsentConfig($this->createConsent(1)),
                    new ConsentConfig($this->createConsent(2))
                ],
                'consent' => $this->createConsent(1),
                'website' => $this->createWebsite(1)
            ],
            'config for website should not be updated' => [
                'config' => [
                    'value' => [
                        ['consent' => 1, 'sort_order' => 1]
                    ],
                    ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => false
                ],
                'convertedConfig' => [
                    new ConsentConfig($this->createConsent(1))
                ],
                'consent' => $this->createConsent(3),
                'website' => $this->createWebsite(1)
            ]
        ];
    }

    /**
     * @dataProvider configProvider
     */
    public function testUpdateConsentsConfigForWebsiteScope(
        array $config,
        array $convertedConfig,
        Consent $consent,
        Website $website
    ) {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::ENABLED_CONSENTS))
            ->willReturn($config);
        if ($config[ConfigManager::USE_PARENT_SCOPE_VALUE_KEY]) {
            $this->converter->expects($this->never())
                ->method('convertFromSaved');
            $this->converter->expects($this->never())
                ->method('convertBeforeSave');
        } else {
            $configValue = $config[ConfigManager::VALUE_KEY];
            $this->converter->expects($this->once())
                ->method('convertFromSaved')
                ->with($configValue)
                ->willReturn($convertedConfig);
            $consentIds = array_column($configValue, ConsentConfigConverter::CONSENT_KEY);
            if (in_array($consent->getId(), $consentIds, true)) {
                $this->converter->expects($this->once())
                    ->method('convertBeforeSave');
                $this->configManager->expects($this->once())
                    ->method('set');
                $this->configManager->expects($this->once())
                    ->method('flush');
            }
        }
        $this->consentConfigManager->updateConsentsConfigForWebsiteScope($consent, $website);
    }

    public function globalConfigProvider(): array
    {
        return [
            'global config must be updated' => [
                'config' => [
                    'value' => [
                        ['consent' => 1, 'sort_order' => 1],
                        ['consent' => 2, 'sort_order' => 2]
                    ],
                    ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => false
                ],
                'convertedConfig' => [
                    new ConsentConfig($this->createConsent(1)),
                    new ConsentConfig($this->createConsent(2))
                ],
                'consent' => $this->createConsent(1),
                'website' => $this->createWebsite(1)
            ],
            'global config should not be updated' => [
                'config' => [
                    'value' => [
                        ['consent' => 1, 'sort_order' => 1]
                    ],
                    ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => false
                ],
                'convertedConfig' => [
                    new ConsentConfig($this->createConsent(1))
                ],
                'consent' => $this->createConsent(3)
            ]
        ];
    }

    /**
     * @dataProvider globalConfigProvider
     */
    public function testUpdateConsentsConfigForGlobalScope(
        array $config,
        array $convertedConfig,
        Consent $consent
    ) {
        $this->globalConfig->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::ENABLED_CONSENTS))
            ->willReturn($config);

        $configValue = $config[ConfigManager::VALUE_KEY];
        $this->converter->expects($this->once())
            ->method('convertFromSaved')
            ->with($configValue)
            ->willReturn($convertedConfig);
        $consentIds = array_column($configValue, ConsentConfigConverter::CONSENT_KEY);
        if (in_array($consent->getId(), $consentIds, true)) {
            $this->converter->expects($this->once())
                ->method('convertBeforeSave');
            $this->globalConfig->expects($this->once())
                ->method('set');
            $this->globalConfig->expects($this->once())
                ->method('flush');
        }

        $this->consentConfigManager->updateConsentsConfigForGlobalScope($consent);
    }

    private function createConsent(int $id): Consent
    {
        return $this->getEntity(Consent::class, ['id' => $id]);
    }

    private function createWebsite(int $id): Website
    {
        return $this->getEntity(Website::class, ['id' => $id]);
    }
}
