<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;

class UnlockAndRecalculate extends TransitionServiceAbstract
{
    public function execute(WorkflowItem $workflowItem): void
    {
        $data = $workflowItem->getData();
        $data->offsetSet('payment_method', null);
        $data->offsetSet('payment_in_progress', false);
        $data->offsetSet('shipping_data_ready', false);
    }
}
