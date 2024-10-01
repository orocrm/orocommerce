<?php

namespace Oro\Bundle\PricingBundle\Expression;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Expression\QueryConverterExtensionInterface;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Component\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;

/**
 * Adds joins for price lists and price list prices if they are requested to an ORM query
 * created by {@see \Oro\Bundle\ProductBundle\Expression\QueryConverter}.
 */
class PriceListQueryConverterExtension implements QueryConverterExtensionInterface
{
    const TABLE_ALIAS_PREFIX = '_plt';

    /**
     * @var int
     */
    protected $tableSuffixCounter = 0;

    /**
     * @var array
     */
    protected $tableAliasByColumn = [];

    #[\Override]
    public function convert(AbstractQueryDesigner $source, QueryBuilder $queryBuilder)
    {
        $this->tableAliasByColumn = [];
        $definition = QueryDefinitionUtil::decodeDefinition($source->getDefinition());
        if (!empty($definition['price_lists'])) {
            $this->joinPriceLists($definition['price_lists'], $queryBuilder);
        }
        if (!empty($definition['prices'])) {
            $this->joinPriceListPrices($definition['prices'], $queryBuilder);
        }

        return $this->tableAliasByColumn;
    }

    /**
     * @param array|int[] $priceLists
     * @param QueryBuilder $queryBuilder
     */
    protected function joinPriceLists(array $priceLists, QueryBuilder $queryBuilder)
    {
        $tablesKey = QueryExpressionConverterInterface::MAPPING_TABLES;
        $aliases = $queryBuilder->getRootAliases();
        $rootAlias = reset($aliases);
        foreach ($priceLists as $priceListId) {
            $priceListId = (int)$priceListId;
            $columnAlias = $this->getPriceListTableKeyByPriceListId($priceListId);
            if (empty($this->tableAliasByColumn[$tablesKey][$columnAlias])) {
                $priceListToProductTableAlias = $this->generateTableAlias();

                $priceListParameter = ':priceList' . $priceListId;
                $assignedProductJoin = $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq($priceListToProductTableAlias . '.priceList', $priceListParameter),
                    $queryBuilder->expr()->eq($priceListToProductTableAlias . '.product', $rootAlias)
                );
                $queryBuilder->setParameter($priceListParameter, $priceListId);
                $queryBuilder->leftJoin(
                    PriceListToProduct::class,
                    $priceListToProductTableAlias,
                    Join::WITH,
                    $assignedProductJoin
                );

                $priceListTableAlias = $this->generateTableAlias();
                $queryBuilder->leftJoin(
                    PriceList::class,
                    $priceListTableAlias,
                    Join::WITH,
                    $queryBuilder->expr()
                        ->eq($priceListToProductTableAlias . '.priceList', $priceListTableAlias)
                );
                $this->tableAliasByColumn[$tablesKey][$columnAlias] = $priceListTableAlias;
            }
        }
    }

    /**
     * @param array|int[] $priceLists
     * @param QueryBuilder $queryBuilder
     */
    protected function joinPriceListPrices(array $priceLists, QueryBuilder $queryBuilder)
    {
        $tablesKey = QueryExpressionConverterInterface::MAPPING_TABLES;
        $this->joinPriceLists($priceLists, $queryBuilder);

        $aliases = $queryBuilder->getRootAliases();
        $rootAlias = reset($aliases);
        foreach ($priceLists as $priceListId) {
            $priceListId = (int)$priceListId;
            $columnAlias = $this->getPriceTableKeyByPriceListId($priceListId);
            if (empty($this->tableAliasByColumn[$tablesKey][$columnAlias])) {
                $priceListId = (int)$priceListId;
                $priceListTableKey = $this->getPriceListTableKeyByPriceListId($priceListId);
                $priceListTableAlias = $this->tableAliasByColumn[$tablesKey][$priceListTableKey];

                $priceTableAlias = $this->generateTableAlias();
                $joinCondition = $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq($priceTableAlias . '.product', $rootAlias),
                    $queryBuilder->expr()->eq($priceTableAlias . '.priceList', $priceListTableAlias)
                );

                $queryBuilder->leftJoin(
                    ProductPrice::class,
                    $priceTableAlias,
                    Join::WITH,
                    $joinCondition
                );
                $this->tableAliasByColumn[$tablesKey][$columnAlias] = $priceTableAlias;
            }
        }
    }

    /**
     * @param int $priceListId
     * @return string
     */
    protected function getPriceTableKeyByPriceListId($priceListId)
    {
        return PriceList::class . '::prices|' . $priceListId;
    }

    /**
     * @param int $priceListId
     * @return string
     */
    protected function getPriceListTableKeyByPriceListId($priceListId)
    {
        return PriceList::class . '|' . $priceListId;
    }

    /**
     * @return string
     */
    protected function generateTableAlias()
    {
        return self::TABLE_ALIAS_PREFIX . $this->tableSuffixCounter++;
    }
}
