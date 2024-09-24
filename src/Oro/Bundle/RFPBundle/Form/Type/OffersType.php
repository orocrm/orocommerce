<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffersType extends AbstractType
{
    const NAME = 'oro_rfp_request_offers';

    const OFFERS_OPTION = 'offers';

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['offers'] = $options['offers'];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'mapped' => false,
                'expanded' => true,
                self::OFFERS_OPTION => [],
            ]
        );

        $resolver->setDefined(self::OFFERS_OPTION);
        $resolver->setAllowedTypes(self::OFFERS_OPTION, 'array');

        $resolver->setNormalizer(
            'choices',
            function (Options $options) {
                return array_keys($options['offers']);
            }
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
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
}
