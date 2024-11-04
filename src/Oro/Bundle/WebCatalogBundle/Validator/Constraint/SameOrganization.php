<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validate that all Content variant`s attached entities are in the same organization as a Web Catalog
 */
class SameOrganization extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.webcatalog.contentvariant.same_organization.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
