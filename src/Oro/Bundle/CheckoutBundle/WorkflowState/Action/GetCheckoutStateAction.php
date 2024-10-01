<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Action;

use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Get checkout state from the storage
 *
 * Usage:
 * @get_checkout_state:
 *      entity: $checkout
 *      token: $token
 *      attribute: $.result.checkout_state
 */
class GetCheckoutStateAction extends AbstractAction
{
    const OPTION_KEY_ENTITY = 'entity';
    const OPTION_KEY_ATTRIBUTE = 'attribute';
    const OPTION_KEY_TOKEN = 'token';

    /** @var array */
    protected $options;

    /** @var CheckoutDiffStorageInterface */
    protected $diffStorage;

    public function __construct(ContextAccessor $contextAccessor, CheckoutDiffStorageInterface $diffStorage)
    {
        $this->diffStorage = $diffStorage;
        parent::__construct($contextAccessor);
    }

    #[\Override]
    protected function executeAction($context)
    {
        $entityPath = $this->getOption($this->options, self::OPTION_KEY_ENTITY);
        $tokenPath = $this->getOption($this->options, self::OPTION_KEY_TOKEN);
        $attributePath = $this->getOption($this->options, self::OPTION_KEY_ATTRIBUTE);

        $entity = $this->contextAccessor->getValue($context, $entityPath);
        $token = $this->contextAccessor->getValue($context, $tokenPath);

        $state = $this->diffStorage->getState($entity, $token);

        $this->contextAccessor->setValue($context, $attributePath, $state);
    }

    #[\Override]
    public function initialize(array $options)
    {
        $this->throwExceptionIfRequiredParameterEmpty($options, self::OPTION_KEY_ENTITY);
        $this->throwExceptionIfRequiredParameterEmpty($options, self::OPTION_KEY_TOKEN);
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
