<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allows to edit quantity of product variant from matrix form.
 */
class MatrixColumnQuantityType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new NumberToLocalizedStringTransformer($options['precision']));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('precision');
        $resolver->setAllowedTypes('precision', 'int');
    }

    #[\Override]
    public function getParent(): string
    {
        return TextType::class;
    }
}
