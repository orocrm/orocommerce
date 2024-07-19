<?php

namespace Oro\Bundle\OrderBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers Twig functions for the email templates rendering sandbox:
 *  - oro_order_shipping_method_label
 *  - oro_order_get_shipping_trackings
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    /**
     * {@inheritDoc}
     */
    protected function getFunctions(): array
    {
        return [
            'oro_order_shipping_method_label',
            'oro_order_get_shipping_trackings'
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilters(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTags(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            'oro_order.twig.order_shipping',
            'oro_order.twig.order'
        ];
    }
}
