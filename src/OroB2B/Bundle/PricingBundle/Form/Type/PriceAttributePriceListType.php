<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;

class PriceAttributePriceListType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_attribute_price_list';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $builder->getData();

        $builder
            ->add('name', 'text', ['required' => true, 'label' => 'orob2b.pricing.pricelist.name.label'])
            ->add(
                'fieldName',
                'text',
                ['required' => true, 'label' => 'orob2b.pricing.priceattributepricelist.field_name.label']
            )
            ->add(
                'currencies',
                CurrencySelectionType::NAME,
                [
                    'multiple' => true,
                    'required' => true,
                    'label' => 'orob2b.pricing.priceattributepricelist.currencies.label',
                    'additional_currencies' => $priceAttributePriceList ?
                        $priceAttributePriceList->getCurrencies() : [],
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
