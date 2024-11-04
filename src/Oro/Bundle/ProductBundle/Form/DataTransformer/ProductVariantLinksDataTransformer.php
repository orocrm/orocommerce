<?php

namespace Oro\Bundle\ProductBundle\Form\DataTransformer;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Symfony\Component\Form\DataTransformerInterface;

class ProductVariantLinksDataTransformer implements DataTransformerInterface
{
    /**
     * @var Collection
     */
    private $variantLinks;

    #[\Override]
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        $this->variantLinks = $value;

        return [
            'appendVariants' => [],
            'removeVariants' => []
        ];
    }

    #[\Override]
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        $appendVariants = $value['appendVariants'];
        $removeVariants = $value['removeVariants'];

        $this->appendVariants($appendVariants);
        $this->removeVariants($removeVariants);

        return $this->variantLinks;
    }

    /**
     * @param array|Product[] $variants
     */
    private function appendVariants(array $variants)
    {
        foreach ($variants as $variant) {
            $this->variantLinks->add(new ProductVariantLink(null, $variant));
        }
    }

    /**
     * @param Product[] $variants
     */
    private function removeVariants(array $variants)
    {
        if (!$variants) {
            return;
        }

        foreach ($this->variantLinks as $variantLink) {
            if (in_array($variantLink->getProduct(), $variants, true)) {
                $this->variantLinks->removeElement($variantLink);
            }
        }
    }
}
