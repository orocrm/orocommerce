<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Oro\Bundle\SaleBundle\Entity;
use Oro\Bundle\SaleBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class QuoteProductValidator extends ConstraintValidator
{
    /**
     *
     * @param Entity\QuoteProduct $quoteProduct
     * @param Constraints\QuoteProduct $constraint
     */
    #[\Override]
    public function validate($quoteProduct, Constraint $constraint)
    {
        if (!$quoteProduct instanceof Entity\QuoteProduct) {
            throw new UnexpectedTypeException(
                $quoteProduct,
                'Oro\Bundle\SaleBundle\Entity\QuoteProduct'
            );
        }

        if ($quoteProduct->isTypeNotAvailable()) {
            $product = $quoteProduct->getProductReplacement();
            $isProductFreeForm = $quoteProduct->isProductReplacementFreeForm();
            $fieldPath = 'productReplacement';
        } else {
            $product = $quoteProduct->getProduct();
            $isProductFreeForm = $quoteProduct->isProductFreeForm();
            $fieldPath = 'product';
        }

        if (!$isProductFreeForm && null === $product) {
            $this->addViolation($fieldPath, $constraint);
            return;
        }
    }

    protected function addViolation($fieldPath, Constraints\QuoteProduct $constraint)
    {
        $this->context->buildViolation($constraint->message)
            ->atPath($fieldPath)
            ->addViolation();
    }
}
