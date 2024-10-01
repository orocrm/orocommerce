<?php

namespace Oro\Bundle\CMSBundle\Async\Extension;

use Oro\Bundle\CMSBundle\EventListener\WYSIWYGFieldTwigListener;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * A workaround which imitates an "end of execution" event for WYSIWYGFieldTwigListener - like kernel.terminate
 * in HttpKernel.
 */
class WYSIWYGFieldTwigConsumptionExtension extends AbstractExtension
{
    /** @var WYSIWYGFieldTwigListener */
    private $wysiwygFieldTwigListener;

    public function __construct(WYSIWYGFieldTwigListener $wysiwygFieldTwigListener)
    {
        $this->wysiwygFieldTwigListener = $wysiwygFieldTwigListener;
    }

    #[\Override]
    public function onPostReceived(Context $context): void
    {
        $this->wysiwygFieldTwigListener->onTerminate();
    }
}
