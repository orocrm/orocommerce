<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Abstract Form type for order line item
 */
abstract class AbstractOrderLineItemType extends AbstractType
{
    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var SectionProvider
     */
    private $sectionProvider;

    /**
     * @return SectionProvider
     */
    public function getSectionProvider()
    {
        if (!$this->sectionProvider) {
            throw new \BadMethodCallException('SectionProvider not injected');
        }

        return $this->sectionProvider;
    }

    public function setSectionProvider(SectionProvider $sectionProvider)
    {
        $this->sectionProvider = $sectionProvider;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'productUnit',
                ProductUnitSelectionType::class,
                [
                    'label' => 'oro.product.productunit.entity_label',
                    'required' => true,
                ]
            )
            ->add(
                'quantity',
                QuantityType::class,
                [
                    'required' => true,
                    'label' => 'oro.order.orderlineitem.quantity.label',
                    'default_data' => 1,
                ]
            )
            ->add(
                'shipBy',
                OroDateType::class,
                [
                    'required' => false,
                    'label' => 'oro.order.orderlineitem.ship_by.label',
                ]
            )
            ->add(
                'comment',
                TextareaType::class,
                [
                    'required' => false,
                    'label' => 'oro.order.orderlineitem.comment.label',
                ]
            );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                /** @var OrderLineItem $item */
                $item = $form->getData();
                if ($item) {
                    $this->updateAvailableUnits($form);
                }
            }
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['currency']);
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'csrf_token_id' => 'order_line_item',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => [],
                'currency' => null,
            ]
        );
        $resolver->setAllowedTypes('page_component_options', 'array');
        $resolver->setAllowedTypes('page_component', 'string');
        $resolver->setAllowedTypes('currency', ['null', 'string']);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->getSectionProvider()->addSections(
            \get_class($this),
            [
                'quantity' => ['data' => ['quantity' => [], 'productUnit' => []], 'order' => 10],
                'price' => [
                    'data' => [
                        'price' => [
                            'page_component' => 'oroui/js/app/components/view-component',
                            'page_component_options' => [
                                'view' => 'oropricing/js/app/views/line-item-product-prices-view',
                            ],
                        ],
                        'priceType' => []
                    ],
                    'order' => 20
                ],
                'ship_by' => ['data' => ['shipBy' => []], 'order' => 30],
                'comment' => [
                    'data' => ['comment' => ['page_component' => 'oroorder/js/app/components/notes-component']],
                    'order' => 40,
                ],
            ]
        );

        if (array_key_exists('page_component', $options)) {
            $view->vars['page_component'] = $options['page_component'];
        } else {
            $view->vars['page_component'] = null;
        }

        if (array_key_exists('page_component_options', $options)) {
            $view->vars['page_component_options'] = $options['page_component_options'];
        }
        $view->vars['page_component_options']['currency'] = $options['currency'];
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['sections'] = $this->getSectionProvider()->getSections(get_class($this));

        $product = null;
        $checksum = '';
        if ($view->vars['value']) {
            /* @var $lineItem OrderLineItem */
            $lineItem = $view->vars['value'];

            if ($lineItem->getProduct()) {
                $product = $lineItem->getProduct();
            }

            $checksum = $lineItem->getChecksum();
        }

        $view->vars['page_component_options']['fullName'] = $view->vars['full_name'];

        if ($product) {
            $modelAttr['product_units'] = $product->getAvailableUnitsPrecision();
            $modelAttr['checksum'] = $checksum;
            $view->vars['page_component_options']['modelAttr'] = $modelAttr;
        }
    }

    abstract protected function updateAvailableUnits(FormInterface $form);
}
