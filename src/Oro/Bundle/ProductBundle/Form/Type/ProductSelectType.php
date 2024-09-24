<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for Product select.
 */
class ProductSelectType extends AbstractType
{
    const NAME = 'oro_product_select';
    const DATA_PARAMETERS = 'data_parameters';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $options;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                self::DATA_PARAMETERS => [],
                'autocomplete_alias' => 'oro_product_visibility_limited',
                'create_form_route' => 'oro_product_create',
                'empty_label' => 'oro.product.removed',
                'configs' => [
                    'placeholder' => 'oro.product.form.choose',
                    'result_template_twig' => '@OroProduct/Product/Autocomplete/result.html.twig',
                    'selection_template_twig' => '@OroProduct/Product/Autocomplete/selection.html.twig',
                ],
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

    #[\Override]
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options;
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!empty($options[self::DATA_PARAMETERS])) {
            $view->vars['attr']['data-select2_query_additional_params'] = json_encode(
                [self::DATA_PARAMETERS => $options[self::DATA_PARAMETERS]]
            );
        }

        $form = $form->getParent();

        /* @var $productHolder ProductHolderInterface */
        $productHolder = $form ? $form->getData() : null;

        if (!$productHolder instanceof ProductHolderInterface || !$productHolder->getEntityIdentifier()) {
            return;
        }

        if (!$productHolder->getProduct()) {
            $emptyValueTitle = $this->translator->trans(
                (string) $options['empty_label'],
                ['{title}' => $productHolder->getProductSku()]
            );
            $view->vars['configs']['placeholder'] = $emptyValueTitle;
        }
    }
}
