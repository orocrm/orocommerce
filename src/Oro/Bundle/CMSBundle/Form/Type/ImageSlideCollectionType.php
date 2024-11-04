<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for the ImageSlide collection.
 */
class ImageSlideCollectionType extends AbstractType
{
    #[\Override]
    public function getParent(): string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['entry_type' => ImageSlideType::class]);
    }
}
