<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelper;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides titles for certain line items group using field path and line item data.
 */
class LineItemGroupTitleProvider
{
    private array $titlePathMapping;
    private PropertyAccessorInterface $propertyAccessor;
    private EntityNameResolver $entityNameResolver;
    private TranslatorInterface $translator;

    public function __construct(
        array $titlePathMapping,
        PropertyAccessorInterface $propertyAccessor,
        EntityNameResolver $entityNameResolver,
        TranslatorInterface $translator
    ) {
        $this->titlePathMapping = $titlePathMapping;
        $this->propertyAccessor = $propertyAccessor;
        $this->entityNameResolver = $entityNameResolver;
        $this->translator = $translator;
    }

    public function getTitle(string $lineItemGroupKey, CheckoutLineItem $lineItem): string
    {
        if (GroupLineItemHelper::OTHER_ITEMS_KEY === $lineItemGroupKey) {
            return $this->translator->trans('oro.checkout.line_items_grouping.other_items_group.title');
        }

        $paths = explode(GroupLineItemHelper::GROUPING_DELIMITER, $lineItemGroupKey);
        $propertyPath = $paths[0];
        if (\array_key_exists($propertyPath, $this->titlePathMapping)) {
            $propertyPath = $this->titlePathMapping[$propertyPath];
        }

        $value = $this->propertyAccessor->getValue($lineItem, $propertyPath);
        if (\is_object($value)) {
            return $this->entityNameResolver->getName($value);
        }

        return (string)$value;
    }
}
