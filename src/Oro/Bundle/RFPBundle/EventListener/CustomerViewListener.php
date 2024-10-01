<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\CustomerBundle\EventListener\AbstractCustomerViewListener;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

/**
 * Adds additional block with RFP grid on the Customer and CustomerUser view page.
 */
class CustomerViewListener extends AbstractCustomerViewListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    #[\Override]
    public function onCustomerView(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }
        parent::onCustomerView($event);
    }

    #[\Override]
    public function onCustomerUserView(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }
        parent::onCustomerUserView($event);
    }

    #[\Override]
    protected function getCustomerViewTemplate()
    {
        return '@OroRFP/Customer/rfp_view.html.twig';
    }

    #[\Override]
    protected function getCustomerLabel(): string
    {
        return 'oro.rfp.datagrid.customer.label';
    }

    #[\Override]
    protected function getCustomerUserViewTemplate()
    {
        return '@OroRFP/CustomerUser/rfp_view.html.twig';
    }

    #[\Override]
    protected function getCustomerUserLabel(): string
    {
        return 'oro.rfp.datagrid.customer_user.label';
    }
}
