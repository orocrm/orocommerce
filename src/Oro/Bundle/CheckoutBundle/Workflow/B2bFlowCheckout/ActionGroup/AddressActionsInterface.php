<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;


use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * Checkout workflow Address-related actions.
 */
interface AddressActionsInterface
{
    public function updateBillingAddress(Checkout $checkout, bool $disallowShippingAddressEdit = false): array;

    public function updateShippingAddress(Checkout $checkout): void;

    public function duplicateOrderAddress(OrderAddress $address): array;
}
