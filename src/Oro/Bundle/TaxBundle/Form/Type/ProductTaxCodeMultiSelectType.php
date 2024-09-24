<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select several product tax codes.
 */
class ProductTaxCodeMultiSelectType extends AbstractType
{
    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_tax_product_tax_code_multiselect';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroJquerySelect2HiddenType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete_alias' => 'oro_product_tax_code',
            'configs'            => ['multiple' => true, 'forceSelectedData' => true]
        ]);
    }
}
