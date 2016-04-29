<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface PaymentMethodInterface
{
    const AUTHORIZE = 'authorize';
    const CHARGE = 'charge';
    const VALIDATE = 'validate';
    const DELAYED_CAPTURE = 'delayedCapture';

    /**
     * Action to wrap action combination - charge, authorize, authorize and capture
     */
    const PURCHASE = 'purchase';

    const CAPTURE = 'capture';

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    public function execute(PaymentTransaction $paymentTransaction);

    /**
     * @return string
     */
    public function getType();

    /**
     * @return bool
     */
    public function isEnabled();

    /**
     * @param string $actionName
     * @return bool
     */
    public function supports($actionName);
}
