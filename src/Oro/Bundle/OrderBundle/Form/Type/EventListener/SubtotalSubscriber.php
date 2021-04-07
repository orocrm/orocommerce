<?php

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Handler\OrderLineItemCurrencyHandler;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Calculates prices for order line items and totals for an order
 * and adds a validator to "discountsSum" form field to disallow submitting an order
 * when the sum of all discounts is exceeded the order grand total amount.
 */
class SubtotalSubscriber implements EventSubscriberInterface
{
    /** @var TotalHelper  */
    protected $totalHelper;

    /** @var PriceMatcher */
    protected $priceMatcher;

    /** @var OrderLineItemCurrencyHandler */
    protected $orderLineItemCurrencyHandler;

    /**
     * @param TotalHelper $totalHelper
     * @param PriceMatcher $priceMatcher
     */
    public function __construct(TotalHelper $totalHelper, PriceMatcher $priceMatcher)
    {
        $this->totalHelper = $totalHelper;
        $this->priceMatcher = $priceMatcher;
    }

    /**
     * @param OrderLineItemCurrencyHandler $orderLineItemCurrencyHandler
     */
    public function setOrderLineItemCurrencyHandler(OrderLineItemCurrencyHandler $orderLineItemCurrencyHandler): void
    {
        $this->orderLineItemCurrencyHandler = $orderLineItemCurrencyHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [FormEvents::SUBMIT => 'onSubmitEventListener'];
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmitEventListener(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($data instanceof Order) {
            // As the order currency may change, need to reset all prices and recalculate it.
            $this->resetLineItems($form, $data);
            $this->priceMatcher->addMatchingPrices($data);
            $this->totalHelper->fill($data);
            $event->setData($data);

            if ($form->has('discountsSum')) {
                $form->remove('discountsSum');
                $form->add(
                    'discountsSum',
                    HiddenType::class,
                    [
                        'mapped' => false,
                        'constraints' => [new Range(
                            [
                                'min' => PHP_INT_MAX * (-1), //use some big negative number
                                'max' => $data->getSubtotal(),
                                'maxMessage' => 'oro.order.discounts.sum.error.label'
                            ]
                        )]
                    ]
                );
                //submit with new max range value for correct validation
                $form->get('discountsSum')->submit($data->getTotalDiscounts()->getValue());
            }
        }
    }

    /**
     * @param FormInterface $form
     * @param Order $order
     */
    private function resetLineItems(FormInterface $form, Order $order): void
    {
        if ($form->has('lineItems')) {
            $this->orderLineItemCurrencyHandler->resetLineItemsPrices($form->get('lineItems'), $order);
        }
    }
}
