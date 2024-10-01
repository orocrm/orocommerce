<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for product price
 */
class PriceListProductPriceType extends AbstractType
{
    const NAME = 'oro_pricing_price_list_product_price';

    /**
     * @var string
     */
    protected $dataClass;

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ProductPrice $data */
        $data = $builder->getData();
        $isExisting = $data && $data->getId();

        $currencies = [];
        if ($data->getPriceList()) {
            $currencies = $data->getPriceList()->getCurrencies();
        }

        $builder
            ->add(
                'product',
                ProductSelectType::class,
                [
                    'required' => true,
                    'label' => 'oro.pricing.productprice.product.label',
                    'create_enabled' => false,
                    'disabled' => $isExisting,
                    'error_bubbling' => true,
                ]
            )
            ->add(
                'unit',
                ProductUnitSelectionType::class,
                [
                    'required' => true,
                    'label' => 'oro.pricing.productprice.unit.label',
                    'placeholder' => 'oro.product.form.product_required',
                ]
            )
            ->add(
                'quantity',
                QuantityType::class,
                [
                    'required' => true,
                    'label' => 'oro.pricing.productprice.quantity.label'
                ]
            )
            ->add(
                'price',
                PriceType::class,
                [
                    'required' => true,
                    'compact' => false,
                    'label' => 'oro.pricing.productprice.price.label',
                    'currencies_list' => $currencies,
                    'currency_empty_value' => false,
                    'by_reference' => false,
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        if ($data instanceof ProductPrice && $data->getId()) {
            $event->getForm()
                ->remove('unit')
                ->add(
                    'unit',
                    ProductUnitSelectionType::class,
                    [
                        'required' => true,
                        'label' => 'oro.pricing.productprice.unit.label',
                        'placeholder' => false
                    ]
                );
        }
    }

    public function onPreSubmit(FormEvent $event)
    {
        $submittedData = $event->getData();
        $productPrice = $event->getForm()->getData();
        if (!$productPrice instanceof ProductPrice) {
            return;
        }
        $oldPrice = $productPrice->getPrice();
        if ($submittedData['quantity'] != $productPrice->getQuantity()
            || ($productPrice->getUnit() && $submittedData['unit'] != $productPrice->getUnit()->getCode())
            || $submittedData['price']['value'] != $oldPrice->getValue()
            || $submittedData['price']['currency'] != $oldPrice->getCurrency()
        ) {
            $productPrice->setPriceRule(null);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
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
     * @param string $productClass
     * @return PriceListProductPriceType
     */
    public function setDataClass($productClass)
    {
        $this->dataClass = $productClass;

        return $this;
    }
}
