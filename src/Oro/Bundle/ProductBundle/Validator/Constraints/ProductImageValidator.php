<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Validator for check is ProductImage file empty
 */
class ProductImageValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_image_validator';

    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /**
     * @param File $value
     * @param Constraint $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof File && $value->isEmptyFile() !== '1') {
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->addViolation();
    }
}
