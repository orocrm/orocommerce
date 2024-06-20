<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Configuration;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentBlockRepository;
use Oro\Bundle\CMSBundle\Form\Configuration\ContentBlockBuilder;
use Oro\Bundle\CMSBundle\Form\Type\ContentBlockSelectType;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Form\Configuration\ConfigurationChildBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class ContentBlockBuilderTest extends TestCase
{
    private ContentBlockBuilder $contentBlockBuilder;

    private Packages|MockObject $packages;
    private DataTransformerInterface|MockObject $transformer;
    private ManagerRegistry|MockObject $registry;
    private FormBuilder|MockObject $formBuilder;

    protected function setUp(): void
    {
        $this->packages = $this->createMock(Packages::class);
        $this->transformer = $this->createMock(DataTransformerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->contentBlockBuilder = new ContentBlockBuilder($this->packages, $this->transformer, $this->registry);

        $this->formBuilder = $this->createMock(FormBuilder::class);
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(string $type, bool $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            $this->contentBlockBuilder->supports(['type' => $type])
        );
    }

    public function getSupportsDataProvider(): array
    {
        return [
            ['unknown_type', false],
            [ContentBlockBuilder::getType(), true],
        ];
    }

    /**
     * @dataProvider optionDataProvider
     */
    public function testThatOptionBuiltCorrectly(array $option, array $expected): void
    {
        $this->formBuilder
            ->expects(self::once())
            ->method('add')
            ->with(
                $expected['name'],
                $expected['form_type'],
                $expected['options']
            );

        $this->contentBlockBuilder->buildOption($this->formBuilder, $option);
    }

    public function testThatFinishViewCorrectlyWithFormDataID(): void
    {
        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn(1);

        $this->packages->expects(self::once())
            ->method('getUrl')
            ->with('promotion-content.png')
            ->willReturn('/promotion-content.png');

        $repository = $this->createMock(ContentBlockRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn((new ContentBlock())->setAlias('promotion-content'));

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);


        $themeOption = [
            'previews' => [
                'home-page-slider' => 'home-page-slider.png',
                'promotion-content' => 'promotion-content.png',
            ]
        ];

        $this->contentBlockBuilder->finishView(
            $formView,
            $form,
            [],
            $themeOption
        );

        self::assertEquals($formView->vars['attr'], ['data-preview' => '/promotion-content.png']);
        self::assertEquals($formView->vars['group_attr'] ?? [], [
            'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
            'data-page-component-options' => [
                'autoRender' => true,
                'previewSource' => '/promotion-content.png',
                'defaultPreview' => ''
            ]
        ]);
    }

    /**
     * @dataProvider finishViewDataProvider
     */
    public function testThatFinishViewCorrectly(
        array $themeOption,
        mixed $data,
        array $assets,
        array $expectedAttr,
        array $expectedGroupAttr
    ): void {
        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($data);

        if ($assets['count'] > 0) {
            $this->packages
                ->expects(self::exactly($assets['count']))
                ->method('getUrl')
                ->withConsecutive(...$assets['url'])
                ->willReturnOnConsecutiveCalls(...$assets['fullUrl']);
        } else {
            $this->packages
                ->expects(self::never())
                ->method('getUrl');
        }

        $this->contentBlockBuilder->finishView(
            $formView,
            $form,
            [],
            $themeOption
        );

        self::assertEquals($expectedAttr, $formView->vars['attr']);
        self::assertEquals($expectedGroupAttr, $formView->vars['group_attr'] ?? []);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function finishViewDataProvider(): array
    {
        return [
            'without previews key' => [
                'themeOption' => [],
                'data' => null,
                'assets' => [
                    'count' => 0,
                    'url' => [],
                    'fullUrl' => []
                ],
                'expectedAttr' => [],
                'expectedGroupAttr' => [],
            ],
            'with empty previews key' => [
                'themeOption' => ['previews' => []],
                'data' => null,
                'assets' => [
                    'count' => 0,
                    'url' => [],
                    'fullUrl' => []
                ],
                'expectedAttr' => [],
                'expectedGroupAttr' => [],
            ],
            'with default previews key' => [
                'themeOption' => [
                    'previews' => [
                        ConfigurationChildBuilderInterface::DEFAULT_PREVIEW_KEY => 'default.png'
                    ]
                ],
                'data' => null,
                'assets' => [
                    'count' => 2,
                    'url' => [['default.png'], ['default.png']],
                    'fullUrl' => ['/default.png', '/default.png']
                ],
                'expectedAttr' => [
                    'data-default-preview' => '/default.png',
                    'data-preview' => '/default.png'
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/default.png',
                        'defaultPreview' => '/default.png'
                    ]
                ]
            ],
            'with form entity data' => [
                'themeOption' => [
                    'previews' => [
                        'home-page-slider' => 'home-page-slider.png',
                        'promotion-content' => 'promotion-content.png',
                    ]
                ],
                'data' => (new ContentBlock())->setAlias('home-page-slider'),
                'assets' => [
                    'count' => 1,
                    'url' => [['home-page-slider.png']],
                    'fullUrl' => ['/home-page-slider.png']
                ],
                'expectedAttr' => [
                    'data-preview' => '/home-page-slider.png',
                ],
                'expectedGroupAttr' => [
                    'data-page-component-view' => ConfigurationChildBuilderInterface::VIEW_MODULE_NAME,
                    'data-page-component-options' => [
                        'autoRender' => true,
                        'previewSource' => '/home-page-slider.png',
                        'defaultPreview' => ''
                    ]
                ]
            ]
        ];
    }

    private function optionDataProvider(): array
    {
        return [
            'no previews' => [
                [
                    'name' => ThemeConfiguration::buildOptionKey('general', 'promotional_content'),
                    'label' => 'Select',
                    'type' => 'content_block_selector',
                    'default' => null,
                    'options' => [
                        'required' => false,
                    ]
                ],
                [
                    'name' => ThemeConfiguration::buildOptionKey('general', 'promotional_content'),
                    'form_type' => ContentBlockSelectType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Select',
                        'attr' => [],
                        'choice_attr' => function () {
                        }
                    ]
                ]
            ]
        ];
    }

    public function testThatBuilderDataReverseTransformConfigured(): void
    {
        $this->transformer
            ->expects(self::any())
            ->method('transform')
            ->willReturn('identifier');

        $this->formBuilder
            ->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::callback(function (CallbackTransformer $callbackTransformer) {
                self::assertEquals([], $callbackTransformer->reverseTransform([]));
                self::assertEquals(
                    ['blockName' => 'identifier'],
                    $callbackTransformer->reverseTransform(['blockName' => 'identifier'])
                );
                self::assertEquals(
                    ['blockName' => 'identifier'],
                    $callbackTransformer->reverseTransform(['blockName' => new \stdClass()])
                );

                return true;
            }));
        $this->formBuilder->expects(self::once())
            ->method('add')
            ->with(
                'blockName',
                ContentBlockSelectType::class,
                [
                    'required' => false,
                    'label' => 'label',
                    'attr' => [],
                    'choice_attr' => function () {
                    }
                ]
            );

        $this->contentBlockBuilder->buildOption(
            $this->formBuilder,
            [
                'name' => 'blockName',
                'label' => 'label',
                'default' => 'default',
                'options' => [
                    'required' => false,
                ],
            ]
        );
    }

    public function testThatBuilderDataTransformConfigured(): void
    {
        $this->transformer
            ->expects(self::any())
            ->method('reverseTransform')
            ->with('identifier')
            ->willReturn(new \stdClass());

        $this->formBuilder
            ->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::callback(function (CallbackTransformer $callbackTransformer) {
                self::assertEquals([], $callbackTransformer->transform([]));
                self::assertEquals(
                    ['blockName' => new \stdClass()],
                    $callbackTransformer->transform(['blockName' => new \stdClass()])
                );
                self::assertEquals(
                    ['blockName' => new \stdClass()],
                    $callbackTransformer->transform(['blockName' => 'identifier'])
                );

                return true;
            }));
        $this->formBuilder->expects(self::once())
            ->method('add')
            ->with(
                'blockName',
                ContentBlockSelectType::class,
                [
                    'required' => false,
                    'label' => 'label',
                    'attr' => [],
                    'choice_attr' => function () {
                    }
                ]
            );

        $this->contentBlockBuilder->buildOption(
            $this->formBuilder,
            [
                'name' => 'blockName',
                'label' => 'label',
                'default' => 'default',
                'options' => [
                    'required' => false,
                ],
            ]
        );
    }
}
