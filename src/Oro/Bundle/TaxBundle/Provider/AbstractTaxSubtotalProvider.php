<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\CacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Model\Result;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract class of taxes subtotal provider.
 */
abstract class AbstractTaxSubtotalProvider implements SubtotalProviderInterface, CacheAwareInterface
{
    public const TYPE = 'tax';

    public function __construct(
        protected TranslatorInterface $translator,
        protected TaxProviderRegistry $taxProviderRegistry,
        protected TaxFactory $taxFactory,
        protected TaxationSettingsProvider $taxationSettingsProvider
    ) {
    }

    #[\Override]
    public function getSubtotal($entity)
    {
        $subtotal = $this->createSubtotal();

        try {
            $tax = $this->getProvider()->getTax($entity);
            $this->fillSubtotal($subtotal, $tax, $entity);
        } catch (TaxationDisabledException $e) {
        }

        return $subtotal;
    }

    #[\Override]
    public function getCachedSubtotal($entity)
    {
        $subtotal = $this->createSubtotal();
        try {
            $tax = $this->getProvider()->loadTax($entity);
            $this->fillSubtotal($subtotal, $tax, $entity);
        } catch (TaxationDisabledException $e) {
        }

        return $subtotal;
    }

    abstract protected function createSubtotal(): Subtotal;

    abstract protected function fillSubtotal(Subtotal $subtotal, Result $tax, ?object $entity = null): Subtotal;

    #[\Override]
    public function isSupported($entity): bool
    {
        return $this->taxFactory->supports($entity);
    }

    #[\Override]
    public function supportsCachedSubtotal($entity): bool
    {
        return $this->taxFactory->supports($entity);
    }

    protected function getProvider(): TaxProviderInterface
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
