<?php

namespace Oro\Bundle\PricingBundle\Form\Type\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds form for ProductPriceFilter
 * adds 'unit' Choice field with choices from Oro\Bundle\ProductBundle\Entity\ProductUnit repository
 */
class ProductPriceFilterType extends AbstractType
{
    const NAME = 'oro_pricing_product_price_filter';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var UnitLabelFormatterInterface
     */
    protected $formatter;

    public function __construct(
        TranslatorInterface $translator,
        ManagerRegistry $registry,
        UnitLabelFormatterInterface $formatter
    ) {
        $this->translator = $translator;
        $this->registry = $registry;
        $this->formatter = $formatter;
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

    #[\Override]
    public function getParent(): ?string
    {
        return NumberRangeFilterType::class;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'unit',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => $this->getUnitChoices(),
            ]
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $operatorChoices = [
            $this->translator->trans('oro.filter.form.label_type_range_between')
                => NumberRangeFilterType::TYPE_BETWEEN,
            $this->translator->trans('oro.filter.form.label_type_range_equals')
                => NumberRangeFilterType::TYPE_EQUAL,
            $this->translator->trans('oro.filter.form.label_type_range_more_than')
                => NumberRangeFilterType::TYPE_GREATER_THAN,
            $this->translator->trans('oro.filter.form.label_type_range_less_than')
                => NumberRangeFilterType::TYPE_LESS_THAN,
            $this->translator->trans('oro.filter.form.label_type_range_more_equals')
                => NumberRangeFilterType::TYPE_GREATER_EQUAL,
            $this->translator->trans('oro.filter.form.label_type_range_less_equals')
                => NumberRangeFilterType::TYPE_LESS_EQUAL,
        ];

        $resolver->setDefaults([
            'data_type' => NumberRangeFilterType::DATA_DECIMAL,
            'operator_choices' => $operatorChoices,
        ]);
    }

    /**
     * Get choices list for unit field.
     *
     * @return array
     */
    protected function getUnitChoices()
    {
        $unitCodes = $this->registry
            ->getManagerForClass(ProductUnit::class)
            ->getRepository(ProductUnit::class)
            ->getAllUnitCodes();

        $choices = [];
        foreach ($unitCodes as $unitCode) {
            $choices[$this->formatter->format($unitCode)] = $unitCode;
        }

        return $choices;
    }
}
