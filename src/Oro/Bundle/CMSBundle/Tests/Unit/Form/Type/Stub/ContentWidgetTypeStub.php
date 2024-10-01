<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ContentWidgetTypeStub extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('param', TextType::class);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_content_widget_stub';
    }
}
