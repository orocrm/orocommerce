<?php

namespace Oro\Bundle\TaxBundle\Calculator;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * (inclTax * taxRate) / (1 + taxRate)
 */
class IncludedTaxCalculator extends AbstractTaxCalculator
{
    #[\Override]
    protected function doCalculate(string $amount, string $taxRate): ResultElement
    {
        $inclTax = BigDecimal::of($amount);
        $taxRate = BigDecimal::of($taxRate)->abs();

        $taxAmount = $inclTax
            ->multipliedBy($taxRate)
            ->dividedBy($taxRate->plus(1), TaxationSettingsProvider::CALCULATION_SCALE, RoundingMode::HALF_UP);

        $exclTax = $inclTax->minus($taxAmount);

        return ResultElement::create($inclTax, $exclTax, $taxAmount);
    }

    #[\Override]
    public function getAmountKey()
    {
        return ResultElement::INCLUDING_TAX;
    }
}
