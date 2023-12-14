<?php

namespace Oro\Bundle\ProductBundle\ContentWidget;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSegmentContentWidgetSettingsType;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Length;
use Twig\Environment;

/**
 * Type for the product segment widgets.
 */
class ProductSegmentContentWidgetType implements ContentWidgetTypeInterface
{
    private int $instanceNumber = 0;

    private const DEFAULT_WIDGET_OPTIONS = [
        'slidesToShow' => 4,
        'responsive' => [
            ['breakpoint' => 1100, 'settings' => ['arrows' => true]],
            ['breakpoint' => 768, 'settings' => ['slidesToShow' => 2, 'arrows' => true]],
            ['breakpoint' => 641, 'settings' => ['slidesToShow' => 1, 'arrows' => true]],
        ],
    ];

    public function __construct(
        private ManagerRegistry $registry
    ) {
    }

    /** {@inheritdoc} */
    public static function getName(): string
    {
        return 'product_segment';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.product.content_widget_type.product_segment.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array
    {
        $data = $this->getWidgetData($contentWidget);

        return [
            [
                'title' => 'oro.product.sections.options',
                'subblocks' => [
                    [
                        'data' => [
                            $twig->render('@OroProduct/ProductSegmentContentWidget/options.html.twig', $data),
                        ]
                    ],
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): ?FormInterface
    {
        return $formFactory
            ->create(ProductSegmentContentWidgetSettingsType::class)
            ->add('labels', LocalizedFallbackValueCollectionType::class, [
                'priority' => 10,
                'data' => $contentWidget->getLabels()->toArray(),
                'label' => 'oro.product.content_widget_type.product_segment.options.labels.singular_label',
                'tooltip' => 'oro.product.content_widget_type.product_segment.options.labels.tooltip',
                'required' => false,
                'block' => 'options',
                'entry_options'  => [
                    'constraints' => [new Length(['max' => 255])],
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetData(ContentWidget $contentWidget): array
    {
        $data = $contentWidget->getSettings();
        $segment = $this->getProductSegment($data);
        unset($data['segment']);

        $data['slider_options']['data'] = array_merge($data, self::DEFAULT_WIDGET_OPTIONS);

        $data['product_segment'] = $segment;
        $data['instanceNumber'] = $this->instanceNumber++;
        $data['contentWidgetName'] = $contentWidget->getName();
        $data['defaultLabel'] = $contentWidget->getDefaultLabel();
        $data['labels'] = $contentWidget->getLabels();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function isInline(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTemplate(ContentWidget $contentWidget, Environment $twig): string
    {
        return '';
    }

    private function getProductSegment(array $data): ?Segment
    {
        return isset($data['segment']) ? $this->registry->getRepository(Segment::class)->find($data['segment']) : null;
    }
}
