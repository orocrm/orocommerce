<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderDiscount;
use OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class SubtotalSubscriber implements EventSubscriberInterface
{
    /** @var TotalProcessorProvider */
    protected $totalProvider;

    /** @var LineItemSubtotalProvider */
    protected $lineItemSubtotalProvider;

    /** @var DiscountSubtotalProvider */
    protected $discountSubtotalProvider;

    /**
     * @param TotalProcessorProvider $totalProvider
     * @param LineItemSubtotalProvider $lineItemSubtotalProvider
     * @param DiscountSubtotalProvider $discountSubtotalProvider
     */
    public function __construct(
        TotalProcessorProvider $totalProvider,
        LineItemSubtotalProvider $lineItemSubtotalProvider,
        DiscountSubtotalProvider $discountSubtotalProvider
    ) {
        $this->totalProvider = $totalProvider;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
        $this->discountSubtotalProvider = $discountSubtotalProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT   => 'postSubmitEventListener'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmitEventListener(FormEvent $event)
    {
        $data = $event->getData();
        if ($data instanceof Order) {
            $this->fillSubtotals($data);
            $this->fillDiscounts($data);
            $this->fillTotal($data);
            $event->setData($data);
        }
    }

    /**
     * @param Order $order
     */
    protected function fillSubtotals(Order $order)
    {
        $subtotal = $this->lineItemSubtotalProvider->getSubtotal($order);
        if ($subtotal) {
            $order->setSubtotal($subtotal->getAmount());

            foreach ($order->getDiscounts() as $discount) {
                if ($discount->getType() === OrderDiscount::TYPE_AMOUNT) {
                    $discount->setPercent($this->calculatePercent($subtotal, $discount));
                }
            }
        } else {
            $order->setSubtotal(0.0);
        }
    }

    /**
     * @param Order $order
     */
    protected function fillDiscounts(Order $order)
    {
        $discountSubtotals = $this->discountSubtotalProvider->getSubtotal($order);

        $discountSubtotalAmount = new Price();
        if (count($discountSubtotals) > 0) {
            foreach ($discountSubtotals as $discount) {
                $newAmount = $discount->getAmount() + (float) $discountSubtotalAmount->getValue();
                $discountSubtotalAmount->setValue($newAmount);
            }
        }
        $order->setTotalDiscounts($discountSubtotalAmount);
    }

    /**
     * @param Order $order
     */
    protected function fillTotal(Order $order)
    {
        $total = $this->totalProvider->getTotal($order);
        if ($total) {
            $order->setTotal($total->getAmount());
        } else {
            $order->setTotal(0.0);
        }
    }

    /**
     * @param Subtotal $subtotal
     * @param OrderDiscount $discount
     * @return int
     */
    protected function calculatePercent($subtotal, $discount)
    {
        return (int) ($discount->getAmount() / $subtotal->getAmount() * 100);
    }
}
