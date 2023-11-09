<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Component\Exception\UnexpectedTypeException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds scroll blocks with product price and product price attributes data on view and edit pages
 */
class FormViewListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const PRICE_ATTRIBUTES_BLOCK_NAME = 'price_attributes';
    const PRICING_BLOCK_NAME = 'prices';

    const PRICING_BLOCK_PRIORITY = 1650;
    const PRICE_ATTRIBUTES_BLOCK_PRIORITY = 1600;

    private AuthorizationCheckerInterface $authorizationChecker;
    protected TranslatorInterface $translator;
    protected DoctrineHelper $doctrineHelper;
    protected PriceAttributePricesProvider $priceAttributePricesProvider;
    private AclHelper $aclHelper;
    protected FieldAclHelper $fieldAclHelper;

    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        PriceAttributePricesProvider $provider,
        AuthorizationCheckerInterface $authorizationChecker,
        AclHelper $aclHelper
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->priceAttributePricesProvider = $provider;
        $this->authorizationChecker = $authorizationChecker;
        $this->aclHelper = $aclHelper;
    }

    public function setFieldAclHelper(FieldAclHelper $fieldAclHelper): void
    {
        $this->fieldAclHelper = $fieldAclHelper;
    }

    public function onProductView(BeforeListRenderEvent $event)
    {
        $product = $event->getEntity();
        if (!$product instanceof Product) {
            throw new UnexpectedTypeException($product, Product::class);
        }

        if (!$this->fieldAclHelper->isFieldViewGranted($event->getEntity(), 'productPriceAttributesPrices')) {
            return;
        }

        $this->addPriceAttributesViewBlock($event, $product);
        $this->addProductPricesViewBlock($event, $product);
    }

    public function onProductEdit(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $product = $event->getEntity();
        if (!$this->fieldAclHelper->isFieldAvailable($product, 'productPriceAttributesPrices')) {
            return;
        }

        $template = $event->getEnvironment()->render(
            '@OroPricing/Product/prices_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $scrollData = $event->getScrollData();
        $blockLabel = $this->translator->trans('oro.pricing.productprice.entity_plural_label');
        $scrollData->addNamedBlock(self::PRICING_BLOCK_NAME, $blockLabel, 1600);
        $subBlockId = $scrollData->addSubBlock(self::PRICING_BLOCK_NAME);
        $scrollData->addSubBlockData(self::PRICING_BLOCK_NAME, $subBlockId, $template);
    }

    /**
     * @return PriceAttributePriceList[]
     */
    protected function getProductAttributesPriceLists(Product $product): array
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getPriceAttributePriceListRepository()->getPriceAttributesQueryBuilder();
        $qb->where('price_attribute_price_list.organization = :organization')
            ->setParameter('organization', $product->getOrganization());
        $options = [PriceAttributesProductFormExtension::PRODUCT_PRICE_ATTRIBUTES_PRICES => true];

        return $this->aclHelper->apply($qb, BasicPermission::VIEW, $options)->getResult();
    }

    /**
     * @return PriceAttributePriceListRepository|EntityRepository
     */
    protected function getPriceAttributePriceListRepository()
    {
        return $this->doctrineHelper->getEntityRepository(PriceAttributePriceList::class);
    }

    protected function addPriceAttributesViewBlock(BeforeListRenderEvent $event, Product $product)
    {
        $scrollData = $event->getScrollData();
        $blockLabel = $this->translator->trans('oro.pricing.priceattributepricelist.entity_plural_label');
        $scrollData->addNamedBlock(
            self::PRICE_ATTRIBUTES_BLOCK_NAME,
            $blockLabel,
            self::PRICE_ATTRIBUTES_BLOCK_PRIORITY
        );

        $priceLists = $this->getProductAttributesPriceLists($product);
        if (empty($priceLists)) {
            $subBlockId = $scrollData->addSubBlock(self::PRICE_ATTRIBUTES_BLOCK_NAME);
            $template = $event->getEnvironment()
                ->render('@OroPricing/Product/price_attribute_no_data.html.twig', []);
            $scrollData->addSubBlockData(
                self::PRICE_ATTRIBUTES_BLOCK_NAME,
                $subBlockId,
                $template,
                'productPriceAttributesPrices'
            );

            return;
        }

        $subBlocksData = ['even' => '', 'odd' => ''];
        foreach ($priceLists as $key => $priceList) {
            $priceAttributePrices = $this->priceAttributePricesProvider
                ->getPricesWithUnitAndCurrencies($priceList, $product);

            $template = $event->getEnvironment()->render(
                '@OroPricing/Product/price_attribute_prices_view.html.twig',
                [
                    'product' => $product,
                    'priceList' => $priceList,
                    'priceAttributePrices' => $priceAttributePrices,
                ]
            );

            $subBlocksData[$key % 2 === 0 ? 'even' : 'odd'] .= $template;
        }

        $subBlockEvenId = $scrollData->addSubBlock(self::PRICE_ATTRIBUTES_BLOCK_NAME);
        $scrollData->addSubBlockData(
            self::PRICE_ATTRIBUTES_BLOCK_NAME,
            $subBlockEvenId,
            $subBlocksData['even'],
            'productPriceAttributesPrices'
        );

        if (count($priceLists) > 1) {
            $subBlockOddId = $scrollData->addSubBlock(self::PRICE_ATTRIBUTES_BLOCK_NAME);
            $scrollData->addSubBlockData(
                self::PRICE_ATTRIBUTES_BLOCK_NAME,
                $subBlockOddId,
                $subBlocksData['odd'],
                'productPriceAttributesPrices'
            );
        }
    }

    protected function addProductPricesViewBlock(BeforeListRenderEvent $event, Product $product)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        if (!$this->authorizationChecker->isGranted(
            'VIEW',
            sprintf('entity:%s', ProductPrice::class)
        )) {
            return;
        }

        $scrollData = $event->getScrollData();
        $blockLabel = $this->translator->trans('oro.pricing.pricelist.entity_plural_label');
        $scrollData->addNamedBlock(self::PRICING_BLOCK_NAME, $blockLabel, self::PRICING_BLOCK_PRIORITY);
        $priceListSubBlockId = $scrollData->addSubBlock(self::PRICING_BLOCK_NAME);

        $template = $event->getEnvironment()->render(
            '@OroPricing/Product/prices_view.html.twig',
            [
                'entity' => $product,
            ]
        );

        $scrollData->addSubBlockData(
            self::PRICING_BLOCK_NAME,
            $priceListSubBlockId,
            $template,
            'prices'
        );
    }
}
