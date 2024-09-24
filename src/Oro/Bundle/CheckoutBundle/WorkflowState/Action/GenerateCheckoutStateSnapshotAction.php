<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Action;

use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Generate checkout state snapshot
 *
 * Usage:
 * @generate_checkout_state_snapshot:
 *      entity: $checkout
 *      attribute: $.result.checkout_state
 */
class GenerateCheckoutStateSnapshotAction extends AbstractAction
{
    const OPTION_KEY_ENTITY = 'entity';
    const OPTION_KEY_ATTRIBUTE = 'attribute';

    /** @var array */
    protected $options;

    /** @var CheckoutStateDiffManager */
    protected $diffManager;

    public function __construct(ContextAccessor $contextAccessor, CheckoutStateDiffManager $diffManager)
    {
        $this->diffManager = $diffManager;
        parent::__construct($contextAccessor);
    }

    #[\Override]
    protected function executeAction($context)
    {
        $entityPath = $this->getOption($this->options, self::OPTION_KEY_ENTITY);
        $attributePath = $this->getOption($this->options, self::OPTION_KEY_ATTRIBUTE);

        $entity = $this->contextAccessor->getValue($context, $entityPath);

        $state = $this->diffManager->getCurrentState($entity);

        $this->contextAccessor->setValue($context, $attributePath, $state);
    }

    #[\Override]
    public function initialize(array $options)
    {
        $this->throwExceptionIfRequiredParameterEmpty($options, self::OPTION_KEY_ENTITY);
        $this->throwExceptionIfRequiredParameterEmpty($options, self::OPTION_KEY_ATTRIBUTE);

        $this->options = $options;

        return $this;
    }

    /**
     * @param array $options
     * @param string $parameter
     * @throws InvalidParameterException
     */
    protected function throwExceptionIfRequiredParameterEmpty($options, $parameter)
    {
        if (empty($options[$parameter])) {
            throw new InvalidParameterException(sprintf('Parameter "%s" is required', $parameter));
        }
    }
}
