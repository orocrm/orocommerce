<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the Web Catalog
 */
class WebCatalogType extends AbstractType
{
    const NAME = 'oro_web_catalog';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'oro.webcatalog.name.label',
                    'required' => true
                ]
            )
            ->add(
                'description',
                OroRichTextType::class,
                [
                    'label' => 'oro.webcatalog.description.label',
                    'required' => false,
                    'wysiwyg_options' => [
                        'elementpath' => true,
                        'resize' => true,
                    ]
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WebCatalog::class
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
