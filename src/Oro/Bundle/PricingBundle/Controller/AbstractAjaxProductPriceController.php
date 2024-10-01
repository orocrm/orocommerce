<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for AjaxProductPriceControllers (backend and frontend)
 * Is used to handle common logic for ProductPrice related actions
 * see method descriptions for more details
 */
abstract class AbstractAjaxProductPriceController extends AbstractController
{
    /**
     * Get products prices by price list and product ids
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductPricesByCustomer(Request $request)
    {
        $scopeCriteria = $this->container->get(ProductPriceScopeCriteriaRequestHandler::class)
            ->getPriceScopeCriteria();

        $currency = $request->get('currency');
        if (null === $currency) {
            $currencies = $this->container->get(CurrencyProviderInterface::class)->getCurrencyList();
        } else {
            $currencies = [$currency];
        }

        return new JsonResponse(
            $this->container->get(ProductPriceProviderInterface::class)
                ->getPricesByScopeCriteriaAndProducts(
                    $scopeCriteria,
                    $this->getRequestProducts($request),
                    $currencies
                )
        );
    }

    protected function getRequestProducts(Request $request): array
    {
        $productIds = $request->get('product_ids', []);
        $doctrineHelper = $this->container->get(DoctrineHelper::class);
        return array_map(
            function ($productId) use ($doctrineHelper) {
                return $doctrineHelper->getEntityReference(Product::class, $productId);
            },
            array_filter($productIds)
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ProductPriceScopeCriteriaRequestHandler::class,
                CurrencyProviderInterface::class,
                ProductPriceProviderInterface::class,
                DoctrineHelper::class,
            ]
        );
    }
}
