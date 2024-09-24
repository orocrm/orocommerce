<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Assign user selected currency to selected attribute. Applicable only for frontend
 * Usage:
 *
 * @assign_user_currency: $.selectedCurrency
 *
 * Or
 *
 * @assign_user_curreny:
 *     attribute: $.selectedCurrency
 */
class AssignUserCurrencyAction extends AbstractAction
{
    /**
     * @var PropertyPathInterface
     */
    protected $attribute;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    public function __construct(ContextAccessor $contextAccessor, UserCurrencyManager $currencyManager)
    {
        parent::__construct($contextAccessor);

        $this->currencyManager = $currencyManager;
    }

    #[\Override]
    protected function executeAction($context)
    {
        $this->contextAccessor->setValue($context, $this->attribute, $this->currencyManager->getUserCurrency());
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (count($options) !== 1) {
            throw new InvalidParameterException('Only one attribute parameter must be defined');
        }

        $attribute = null;
        if (array_key_exists(0, $options)) {
            $attribute = $options[0];
        } elseif (array_key_exists('attribute', $options)) {
            $attribute = $options['attribute'];
        }

        if (!$attribute) {
            throw new InvalidParameterException('Attribute must be defined');
        }
        if (!$attribute instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition');
        }

        $this->attribute = $attribute;

        return $this;
    }
}
