<?php

namespace Oro\Bundle\ShippingBundle\Context\Builder\Basic;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * The basic implementation of shipping context builder.
 */
class BasicShippingContextBuilder implements ShippingContextBuilderInterface
{
    private object $sourceEntity;
    private mixed $sourceEntityIdentifier;
    private ?Collection $lineItems = null;
    private ?AddressInterface $billingAddress = null;
    private ?AddressInterface $shippingAddress = null;
    private ?AddressInterface $shippingOrigin = null;
    private ?string $paymentMethod = null;
    private ?Customer $customer = null;
    private ?CustomerUser $customerUser = null;
    private ?Price $subTotal = null;
    private ?string $currency = null;
    private ?Website $website = null;

    public function __construct(object $sourceEntity, mixed $sourceEntityIdentifier)
    {
        $this->sourceEntity = $sourceEntity;
        $this->sourceEntityIdentifier = $sourceEntityIdentifier;
    }

    #[\Override]
    public function getResult(): ShippingContextInterface
    {
        $params = $this->getMandatoryParams();
        $params += $this->getOptionalParams();

        return new ShippingContext($params);
    }

    #[\Override]
    public function setLineItems(Collection $lineItemCollection): static
    {
        $this->lineItems = $lineItemCollection;

        return $this;
    }

    #[\Override]
    public function setBillingAddress(?AddressInterface $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    #[\Override]
    public function setShippingAddress(?AddressInterface $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    #[\Override]
    public function setShippingOrigin(?AddressInterface $shippingOrigin): static
    {
        $this->shippingOrigin = $shippingOrigin;

        return $this;
    }

    #[\Override]
    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    #[\Override]
    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    #[\Override]
    public function setCustomerUser(?CustomerUser $customerUser): static
    {
        $this->customerUser = $customerUser;

        return $this;
    }

    #[\Override]
    public function setSubTotal(?Price $subTotal): static
    {
        $this->subTotal = $subTotal;

        return $this;
    }

    #[\Override]
    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    #[\Override]
    public function setWebsite(?Website $website): static
    {
        $this->website = $website;

        return $this;
    }

    private function getMandatoryParams(): array
    {
        return [
            ShippingContext::FIELD_SOURCE_ENTITY => $this->sourceEntity,
            ShippingContext::FIELD_SOURCE_ENTITY_ID => $this->sourceEntityIdentifier,
            ShippingContext::FIELD_LINE_ITEMS => $this->lineItems ?? new ArrayCollection([]),
        ];
    }

    private function getOptionalParams(): array
    {
        $optionalParams = [
            ShippingContext::FIELD_CURRENCY => $this->currency,
            ShippingContext::FIELD_SUBTOTAL => $this->subTotal,
            ShippingContext::FIELD_BILLING_ADDRESS => $this->billingAddress,
            ShippingContext::FIELD_SHIPPING_ADDRESS => $this->shippingAddress,
            ShippingContext::FIELD_PAYMENT_METHOD => $this->paymentMethod,
            ShippingContext::FIELD_CUSTOMER => $this->customer,
            ShippingContext::FIELD_CUSTOMER_USER => $this->customerUser,
            ShippingContext::FIELD_WEBSITE => $this->website,
            ShippingContext::FIELD_SHIPPING_ORIGIN => $this->shippingOrigin,
        ];

        // Exclude NULL elements.
        $optionalParams = array_diff_key($optionalParams, array_filter($optionalParams, 'is_null'));

        return $optionalParams;
    }
}
