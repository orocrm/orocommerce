<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingOptions\Factory;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\LineItemBuilderByLineItemFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

/**
 * Sets shipping options for the shipping line items when options value is null.
 *
 * @deprecated since 5.1
 */
class ShippingOptionsLineItemCollectionFactoryDecorator implements ShippingLineItemCollectionFactoryInterface
{
    private ShippingLineItemCollectionFactoryInterface $decoratedFactory;

    private ManagerRegistry $managerRegistry;

    private LineItemBuilderByLineItemFactoryInterface $builderByLineItemFactory;

    /** @var LengthUnit[] */
    private array $dimensionsUnits = [];

    /** @var WeightUnit[] */
    private array $weightUnits = [];

    public function __construct(
        ShippingLineItemCollectionFactoryInterface $decoratedFactory,
        ManagerRegistry $managerRegistry,
        LineItemBuilderByLineItemFactoryInterface $builderByLineItemFactory
    ) {
        $this->decoratedFactory = $decoratedFactory;
        $this->managerRegistry = $managerRegistry;
        $this->builderByLineItemFactory = $builderByLineItemFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createShippingLineItemCollection(array $shippingLineItems): ShippingLineItemCollectionInterface
    {
        $shippingOptsByCode = $this->getShippingOptionsIndexedByProductId($shippingLineItems);

        if (count($shippingOptsByCode) === 0) {
            return $this->decoratedFactory->createShippingLineItemCollection($shippingLineItems);
        }

        $newShippingLineItems = [];

        foreach ($shippingLineItems as $lineItem) {
            $builder = $this->builderByLineItemFactory->createBuilder($lineItem);
            $unitCode = $lineItem->getProductUnitCode();
            $product = $lineItem->getProduct();
            if ($product !== null && $unitCode !== null && isset($shippingOptsByCode[$product->getId()][$unitCode])) {
                $shippingOptions = $shippingOptsByCode[$product->getId()][$unitCode];
                // this shipping option is not the actual option.
                if ($lineItem->getWeight() === null) {
                    $builder->setWeight(
                        Weight::create(
                            $shippingOptions['weightValue'],
                            $this->getWeightUnit($shippingOptions['weightUnit'])
                        )
                    );
                }

                if ($lineItem->getDimensions() === null) {
                    $builder->setDimensions(
                        Dimensions::create(
                            $shippingOptions['dimensionsLength'],
                            $shippingOptions['dimensionsWidth'],
                            $shippingOptions['dimensionsHeight'],
                            $this->getDimensionsUnit($shippingOptions['dimensionsUnit'])
                        )
                    );
                }
            }

            $newShippingLineItems[] = $this->processKitShippingOptions($builder->getResult(), $shippingOptsByCode);
        }

        $this->dimensionsUnits = [];
        $this->weightUnits = [];

        return $this->decoratedFactory->createShippingLineItemCollection($newShippingLineItems);
    }

    private function processKitShippingOptions(
        ShippingLineItemInterface $shippingLineItem,
        array $shippingOptsByCode
    ): ShippingLineItemInterface {
        if ($shippingLineItem->getProduct()?->isKit()) {
            foreach ($shippingLineItem->getKitItemLineItems() as $kitItemLineItem) {
                $product = $kitItemLineItem->getProduct();
                $unitCode = $kitItemLineItem->getProductUnitCode();
                if (!isset($shippingOptsByCode[$product?->getId()][$unitCode])) {
                    continue;
                }

                $shippingOptions = $shippingOptsByCode[$product->getId()][$unitCode];

                $kitItemLineItem->setWeight(
                    Weight::create(
                        $shippingOptions['weightValue'],
                        $this->getWeightUnit($shippingOptions['weightUnit'])
                    )
                );
                $kitItemLineItem->setDimensions(
                    Dimensions::create(
                        $shippingOptions['dimensionsLength'],
                        $shippingOptions['dimensionsWidth'],
                        $shippingOptions['dimensionsHeight'],
                        $this->getDimensionsUnit($shippingOptions['dimensionsUnit'])
                    )
                );
            }
        }

        return $shippingLineItem;
    }

    /**
     * @param ShippingLineItemInterface[] $shippingLineItems
     *
     * @return array
     */
    private function getShippingOptionsIndexedByProductId(array $shippingLineItems): array
    {
        $unitsByProductIds = $this->getUnitsIndexedByProductId($shippingLineItems);

        return $this->getShippingOptionsRepository()
            ->findIndexedByProductsAndUnits($unitsByProductIds);
    }

    /**
     * @param ShippingLineItemInterface[] $shippingLineItems
     *
     * @return array
     */
    private function getUnitsIndexedByProductId(array $shippingLineItems): array
    {
        $result = [];

        $shippingLineItems = array_merge($shippingLineItems, $this->getKitItemLineItems($shippingLineItems));
        foreach ($shippingLineItems as $shippingLineItem) {
            $product = $shippingLineItem->getProduct();
            $unit = $shippingLineItem->getProductUnit();
            if ($product !== null && $unit !== null) {
                $result[$product->getId()][$unit->getCode()] = $unit;
            }
        }

        return $result;
    }

    private function getShippingOptionsRepository(): ProductShippingOptionsRepository
    {
        return $this->managerRegistry->getRepository(ProductShippingOptions::class);
    }

    private function getDimensionsUnit(?string $lengthUnitCode): ?LengthUnit
    {
        if (!$lengthUnitCode) {
            return null;
        }

        if (!isset($this->dimensionsUnits[$lengthUnitCode])) {
            $this->dimensionsUnits[$lengthUnitCode] = $this->managerRegistry->getManagerForClass(LengthUnit::class)
                ->getReference(LengthUnit::class, $lengthUnitCode);
        }

        return $this->dimensionsUnits[$lengthUnitCode];
    }

    private function getWeightUnit(?string $weightUnitCode): ?WeightUnit
    {
        if (!$weightUnitCode) {
            return null;
        }

        if (!isset($this->weightUnits[$weightUnitCode])) {
            $this->weightUnits[$weightUnitCode] = $this->managerRegistry->getManagerForClass(WeightUnit::class)
                ->getReference(WeightUnit::class, $weightUnitCode);
        }

        return $this->weightUnits[$weightUnitCode];
    }

    /**
     * @param ShippingLineItem[] $lineItems
     *
     * @return ShippingKitItemLineItem[]
     */
    private function getKitItemLineItems(array $lineItems): array
    {
        $kitLineItems = [];
        foreach ($lineItems as $lineItem) {
            if ($lineItem instanceof ProductKitItemLineItemsAwareInterface && $lineItem->getProduct()?->isKit()) {
                $kitLineItems = array_merge($kitLineItems, $lineItem->getKitItemLineItems()->toArray());
            }
        }

        return $kitLineItems;
    }
}
