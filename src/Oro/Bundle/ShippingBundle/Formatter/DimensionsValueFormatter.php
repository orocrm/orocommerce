<?php

namespace Oro\Bundle\ShippingBundle\Formatter;

use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatter;
use Oro\Bundle\ShippingBundle\Model\DimensionsValue;

/**
 * Formats dimensions and provide them in a human-readable format.
 */
class DimensionsValueFormatter extends UnitValueFormatter
{
    /**
     * @param DimensionsValue $value
     * @param string $unitCode
     * @param boolean $isShort
     *
     * @return string
     */
    #[\Override]
    public function formatCode($value, $unitCode, $isShort = false)
    {
        $na = $this->translator->trans('N/A');

        if (!$value instanceof DimensionsValue || $value->isEmpty() || !$unitCode) {
            return $na;
        }

        $unitTranslationKey = sprintf(
            '%s.%s.label.%s',
            $this->getTranslationPrefix(),
            $unitCode,
            $isShort ? 'short' : 'full'
        );

        return sprintf(
            '%s %s',
            $this->formatValue($value, $na),
            $this->translator->trans($unitTranslationKey)
        );
    }

    /**
     * @param DimensionsValue $value
     * @param string $na
     * @return string
     */
    protected function formatValue(DimensionsValue $value, $na)
    {
        return sprintf(
            '%s x %s x %s',
            $this->formatScientificNotation($value->getLength()) ?: $na,
            $this->formatScientificNotation($value->getWidth()) ?: $na,
            $this->formatScientificNotation($value->getHeight()) ?: $na
        );
    }
}
