<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityFieldFilteringHelper;
use Oro\Bundle\CMSBundle\Api\Processor\ConfigureCombinedWYSIWYGFields;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ConfigureCombinedWYSIWYGFieldsTest extends ConfigProcessorTestCase
{
    /** @var WYSIWYGFieldsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $wysiwygFieldsProvider;

    /** @var EntityFieldFilteringHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityFieldFilteringHelper;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigureCombinedWYSIWYGFields */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->wysiwygFieldsProvider = $this->createMock(WYSIWYGFieldsProvider::class);
        $this->entityFieldFilteringHelper = $this->createMock(EntityFieldFilteringHelper::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new ConfigureCombinedWYSIWYGFields(
            $this->wysiwygFieldsProvider,
            $this->entityFieldFilteringHelper,
            $this->doctrineHelper
        );
    }

    private function setWysiwygFieldsExpectation(bool $isSerializedWysiwygField = false): void
    {
        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['wysiwygField']);
        $this->wysiwygFieldsProvider->expects(self::atLeastOnce())
            ->method('getWysiwygStyleField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_style');
        $this->wysiwygFieldsProvider->expects(self::atLeastOnce())
            ->method('getWysiwygPropertiesField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_properties');
        $this->wysiwygFieldsProvider->expects(self::atLeastOnce())
            ->method('isSerializedWysiwygField')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, 'wysiwygField', $isSerializedWysiwygField],
                [self::TEST_CLASS_NAME, 'wysiwygField_style', $isSerializedWysiwygField],
                [self::TEST_CLASS_NAME, 'wysiwygField_properties', $isSerializedWysiwygField]
            ]);

        $this->entityFieldFilteringHelper->expects(self::once())
            ->method('filterEntityFields')
            ->with(self::TEST_CLASS_NAME, ['wysiwygField'], [], self::isNull())
            ->willReturn(['wysiwygField']);
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'someField' => null
            ]
        ];
        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->wysiwygFieldsProvider->expects(self::never())
            ->method('getWysiwygFields');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessWithoutWysiwygFields()
    {
        $config = [
            'fields' => [
                'someField' => null
            ]
        ];
        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcess()
    {
        $this->setWysiwygFieldsExpectation();

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField' => null
            ]
        ]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'wysiwyg_fields'          => ['wysiwygField'],
                'rendered_wysiwyg_fields' => ['wysiwygField.valueRendered' => ['wysiwygField', 'wysiwygField_style']],
                'fields'                  => [
                    'someField'               => null,
                    'wysiwygField'            => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'inherit_data' => true
                        ],
                        'property_path'    => '_',
                        'depends_on'       => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'value'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ],
                            'properties'    => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ],
                            'valueRendered' => [
                                'data_type'     => 'string',
                                'property_path' => '_'
                            ]
                        ]
                    ],
                    '_wysiwygField'           => [
                        'exclude'       => true,
                        'property_path' => 'wysiwygField'
                    ],
                    'wysiwygField_style'      => [
                        'exclude' => true
                    ],
                    'wysiwygField_properties' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForRenamedWysiwygField()
    {
        $this->setWysiwygFieldsExpectation();

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField'           => null,
                'renamedWysiwygField' => [
                    'property_path' => 'wysiwygField'
                ]
            ]
        ]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'wysiwyg_fields'          => ['renamedWysiwygField'],
                'rendered_wysiwyg_fields' => [
                    'renamedWysiwygField.valueRendered' => ['wysiwygField', 'wysiwygField_style']
                ],
                'fields'                  => [
                    'someField'               => null,
                    'renamedWysiwygField'     => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'inherit_data' => true
                        ],
                        'property_path'    => '_',
                        'depends_on'       => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'value'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ],
                            'properties'    => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ],
                            'valueRendered' => [
                                'data_type'     => 'string',
                                'property_path' => '_'
                            ]
                        ]
                    ],
                    '_wysiwygField'           => [
                        'exclude'       => true,
                        'property_path' => 'wysiwygField'
                    ],
                    'wysiwygField_style'      => [
                        'exclude' => true
                    ],
                    'wysiwygField_properties' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForWysiwygFieldWithConfiguredFormOptions()
    {
        $this->setWysiwygFieldsExpectation();

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField'    => null,
                'wysiwygField' => [
                    'form_options' => [
                        'option1' => 'value1'
                    ]
                ]
            ]
        ]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'wysiwyg_fields'          => ['wysiwygField'],
                'rendered_wysiwyg_fields' => ['wysiwygField.valueRendered' => ['wysiwygField', 'wysiwygField_style']],
                'fields'                  => [
                    'someField'               => null,
                    'wysiwygField'            => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'option1'      => 'value1',
                            'inherit_data' => true
                        ],
                        'property_path'    => '_',
                        'depends_on'       => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'value'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ],
                            'properties'    => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ],
                            'valueRendered' => [
                                'data_type'     => 'string',
                                'property_path' => '_'
                            ]
                        ]
                    ],
                    '_wysiwygField'           => [
                        'exclude'       => true,
                        'property_path' => 'wysiwygField'
                    ],
                    'wysiwygField_style'      => [
                        'exclude' => true
                    ],
                    'wysiwygField_properties' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForWysiwygFieldWithRenamedAdditionalFieldsAndOneOfThemIsMarkedAsNotExcluded()
    {
        $this->setWysiwygFieldsExpectation();

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField'                     => null,
                'renamedWysiwygFieldStyle'      => [
                    'property_path' => 'wysiwygField_style',
                    'exclude'       => false
                ],
                'renamedWysiwygFieldProperties' => [
                    'property_path' => 'wysiwygField_properties'
                ]
            ]
        ]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'wysiwyg_fields'          => ['wysiwygField'],
                'rendered_wysiwyg_fields' => ['wysiwygField.valueRendered' => ['wysiwygField', 'wysiwygField_style']],
                'fields'                  => [
                    'someField'                     => null,
                    'wysiwygField'                  => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'inherit_data' => true
                        ],
                        'property_path'    => '_',
                        'depends_on'       => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'value'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ],
                            'properties'    => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ],
                            'valueRendered' => [
                                'data_type'     => 'string',
                                'property_path' => '_'
                            ]
                        ]
                    ],
                    '_wysiwygField'                 => [
                        'property_path' => 'wysiwygField',
                        'exclude'       => true
                    ],
                    'renamedWysiwygFieldStyle'      => [
                        'property_path' => 'wysiwygField_style'
                    ],
                    'renamedWysiwygFieldProperties' => [
                        'property_path' => 'wysiwygField_properties',
                        'exclude'       => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithConfiguredWysiwygNestedFields()
    {
        $this->setWysiwygFieldsExpectation();

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'wysiwygField' => [
                    'fields' => [
                        'value' => [
                            'form_type' => 'ValueFormType'
                        ],
                        'style' => [
                            'form_options' => [
                                'option1' => 'option1_value'
                            ]
                        ]
                    ]
                ]
            ]
        ]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'wysiwyg_fields'          => ['wysiwygField'],
                'rendered_wysiwyg_fields' => ['wysiwygField.valueRendered' => ['wysiwygField', 'wysiwygField_style']],
                'fields'                  => [
                    'wysiwygField'            => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'inherit_data' => true
                        ],
                        'property_path'    => '_',
                        'depends_on'       => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'value'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField',
                                'form_type'     => 'ValueFormType'
                            ],
                            'style'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style',
                                'form_options'  => [
                                    'option1' => 'option1_value'
                                ]
                            ],
                            'properties'    => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ],
                            'valueRendered' => [
                                'data_type'     => 'string',
                                'property_path' => '_'
                            ]
                        ]
                    ],
                    '_wysiwygField'           => [
                        'exclude'       => true,
                        'property_path' => 'wysiwygField'
                    ],
                    'wysiwygField_style'      => [
                        'exclude' => true
                    ],
                    'wysiwygField_properties' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForSerializedWysiwygFields()
    {
        $this->setWysiwygFieldsExpectation(true);

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField' => null
            ]
        ]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'wysiwyg_fields'          => ['wysiwygField'],
                'rendered_wysiwyg_fields' => ['wysiwygField.valueRendered' => ['wysiwygField', 'wysiwygField_style']],
                'fields'                  => [
                    'someField'               => null,
                    'wysiwygField'            => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'inherit_data' => true
                        ],
                        'property_path'    => '_',
                        'depends_on'       => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'value'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ],
                            'properties'    => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ],
                            'valueRendered' => [
                                'data_type'     => 'string',
                                'property_path' => '_'
                            ]
                        ]
                    ],
                    '_wysiwygField'           => [
                        'exclude'       => true,
                        'property_path' => 'wysiwygField',
                        'depends_on'    => ['serialized_data']
                    ],
                    'wysiwygField_style'      => [
                        'exclude'    => true,
                        'depends_on' => ['serialized_data']
                    ],
                    'wysiwygField_properties' => [
                        'exclude'    => true,
                        'depends_on' => ['serialized_data']
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenWysiwygFieldIsNotConfiguredExplicitlyAndFilteredByEntityFieldFilteringHelper()
    {
        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['wysiwygField']);
        $this->wysiwygFieldsProvider->expects(self::atLeastOnce())
            ->method('getWysiwygStyleField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_style');
        $this->wysiwygFieldsProvider->expects(self::atLeastOnce())
            ->method('getWysiwygPropertiesField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_properties');
        $this->wysiwygFieldsProvider->expects(self::atLeastOnce())
            ->method('isSerializedWysiwygField')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, 'wysiwygField', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_style', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_properties', false]
            ]);

        $this->entityFieldFilteringHelper->expects(self::once())
            ->method('filterEntityFields')
            ->with(self::TEST_CLASS_NAME, ['wysiwygField'], ['someField'], ConfigUtil::EXCLUSION_POLICY_ALL)
            ->willReturn([]);

        $this->context->setExplicitlyConfiguredFieldNames(['someField']);
        $this->context->setRequestedExclusionPolicy(ConfigUtil::EXCLUSION_POLICY_ALL);
        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField' => null
            ]
        ]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'wysiwyg_fields'          => ['wysiwygField'],
                'rendered_wysiwyg_fields' => ['wysiwygField.valueRendered' => ['wysiwygField', 'wysiwygField_style']],
                'fields'                  => [
                    'someField'               => null,
                    'wysiwygField'            => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'inherit_data' => true
                        ],
                        'property_path'    => '_',
                        'exclude'          => true,
                        'depends_on'       => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'value'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style'         => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ],
                            'properties'    => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ],
                            'valueRendered' => [
                                'data_type'     => 'string',
                                'property_path' => '_'
                            ]
                        ]
                    ],
                    '_wysiwygField'           => [
                        'exclude'       => true,
                        'property_path' => 'wysiwygField'
                    ],
                    'wysiwygField_style'      => [
                        'exclude' => true
                    ],
                    'wysiwygField_properties' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}
