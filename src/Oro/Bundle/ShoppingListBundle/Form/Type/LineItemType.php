<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for ShoppingList line item
 */
class LineItemType extends AbstractType
{
    const NAME = 'oro_shopping_list_line_item';

    /**
     * @var string
     */
    protected $dataClass;

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var LineItem $data */
        $data = $builder->getData();
        $isExisting = $data && $data->getId();
        $builder
            ->add(
                'product',
                ProductSelectType::class,
                [
                    'required' => true,
                    'label' => 'oro.shoppinglist.lineitem.product.label',
                    'create_enabled' => false,
                    'disabled' => $isExisting,
                    'data_parameters' => [
                        'scope' => 'shopping_list'
                    ]
                ]
            )
            ->add(
                'unit',
                ProductUnitSelectionType::class,
                [
                    'required' => true,
                    'label' => 'oro.shoppinglist.lineitem.unit.label',
                    'product_holder' => $data,
                    'placeholder' => 'oro.product.form.product_required'
                ]
            )
            ->add(
                'quantity',
                QuantityType::class,
                [
                    'required' => true,
                    'label' => 'oro.shoppinglist.lineitem.quantity.label'
                ]
            )
            ->add(
                'notes',
                TextareaType::class,
                [
                    'required' => false,
                    'label' => 'oro.shoppinglist.lineitem.notes.label',
                    'empty_data' => null,
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    public function preSetData(FormEvent $event)
    {
        $entity = $event->getData();
        if (!($entity instanceof LineItem) || !$entity->getId()) {
            return;
        }
        $form = $event->getForm();
        $form->add(
            'unit',
            ProductUnitSelectionType::class,
            [
                'required' => true,
                'label' => 'oro.shoppinglist.lineitem.unit.label',
                'placeholder' => null,
                'product_holder' => $entity,
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    /**
     * @param string $dataClass
     *
     * @return $this
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;

        return $this;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'ownership_disabled' => true,
                'validation_groups' => function (FormInterface $form) {
                    return $form->getData()->getId() ? ['update'] : ['create'];
                },
            ]
        );
    }
}
