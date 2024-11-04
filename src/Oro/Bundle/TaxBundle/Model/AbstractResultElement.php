<?php

namespace Oro\Bundle\TaxBundle\Model;

/**
 * Base class for Tax result element
 */
abstract class AbstractResultElement extends AbstractResult
{
    const CURRENCY = 'currency';

    /**
     * @param string $index
     * @param string $value
     */
    #[\Override]
    public function offsetSet($index, $value): void
    {
        parent::offsetSet((string)$index, (string)$value);
    }

    /**
     * @param string $currency
     * @return AbstractResultElement
     */
    public function setCurrency($currency)
    {
        $this->offsetSet(self::CURRENCY, $currency);

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->getOffset(self::CURRENCY);
    }
}
