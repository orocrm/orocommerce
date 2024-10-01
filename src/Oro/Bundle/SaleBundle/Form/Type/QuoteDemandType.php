<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Manager\QuoteDemandManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents a {@see QuoteDemand}.
 */
class QuoteDemandType extends AbstractType
{
    const NAME = 'oro_sale_quote_demand';

    /**
     * @var QuoteDemandManager
     */
    protected $quoteDemandManager;

    public function __construct(QuoteDemandManager $quoteDemandManager)
    {
        $this->quoteDemandManager = $quoteDemandManager;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['data']);
        $resolver->setDefault('data_class', 'Oro\Bundle\SaleBundle\Entity\QuoteDemand');
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var QuoteDemand $quoteDemand */
        $quoteDemand = $options['data'];
        $builder->add(
            'demandProducts',
            QuoteProductDemandCollectionType::class,
            [
                'data' => $quoteDemand->getDemandProducts()
            ]
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
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

    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if ($data instanceof QuoteDemand) {
            $this->quoteDemandManager->recalculateSubtotals($data);
            $this->quoteDemandManager->updateQuoteProductDemandChecksum($data);
        }
    }
}
