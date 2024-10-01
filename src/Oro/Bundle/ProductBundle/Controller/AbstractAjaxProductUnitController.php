<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Abstract class used for getting product units on frontend
 */
abstract class AbstractAjaxProductUnitController extends AbstractController
{
    /**
     * @return JsonResponse
     */
    protected function getAllProductUnits()
    {
        return new JsonResponse(
            [
                'units' => $this->getProductUnitFormatter()
                    ->formatChoices($this->getRepository()->findBy([], ['code' => 'ASC']))
            ]
        );
    }

    /**
     * @param Product $product
     *
     * @return JsonResponse
     */
    protected function getProductUnits(Product $product)
    {
        return new JsonResponse(
            [
                'units' => $product->getSellUnitsPrecision(),
            ]
        );
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getRepository()
    {
        return $this->container->get('doctrine')
            ->getManagerForClass(ProductUnit::class)
            ->getRepository(ProductUnit::class);
    }

    /**
     * @return UnitLabelFormatterInterface
     */
    protected function getProductUnitFormatter()
    {
        return $this->container->get(UnitLabelFormatter::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                UnitLabelFormatter::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
