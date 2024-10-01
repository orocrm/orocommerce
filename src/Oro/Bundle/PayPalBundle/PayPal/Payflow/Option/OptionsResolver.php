<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver as BaseOptionsResolver;

/**
 * Resolves options for Payflow request.
 */
class OptionsResolver extends BaseOptionsResolver
{
    /**
     * @var OptionsDependentInterface[]
     */
    protected $dependentOptions = [];

    /**
     * @var bool
     */
    protected $addOptionLocked = false;

    /**
     * @param OptionInterface $option
     * @return $this
     */
    public function addOption(OptionInterface $option)
    {
        if ($this->addOptionLocked) {
            throw new AccessException('addOption is locked during resolve process');
        }

        if ($option instanceof OptionsDependentInterface) {
            $this->dependentOptions[] = $option;
        }

        $option->configureOption($this);

        return $this;
    }

    #[\Override]
    public function resolve(array $options = []): array
    {
        $this->addOptionLocked = true;
        $this->handleDependentOptions($options);

        $result = parent::resolve($options);
        $this->addOptionLocked = false;

        return $result;
    }

    protected function handleDependentOptions(array $options)
    {
        foreach ($this->dependentOptions as $dependentOption) {
            if ($dependentOption->isApplicableDependent($options)) {
                $dependentOption->configureDependentOption($this, $options);
            }
        }
    }
}
