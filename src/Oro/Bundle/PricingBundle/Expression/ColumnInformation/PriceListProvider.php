<?php

namespace Oro\Bundle\PricingBundle\Expression\ColumnInformation;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\Expression\ColumnInformationProviderInterface;
use Oro\Component\Expression\Node\NameNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\RelationNode;

class PriceListProvider implements ColumnInformationProviderInterface
{
    #[\Override]
    public function fillColumnInformation(NodeInterface $node, array &$addedColumns, array &$definition)
    {
        if ($node instanceof NameNode) {
            return $this->convertNameNode($node, $addedColumns, $definition);
        } elseif ($node instanceof RelationNode) {
            return $this->convertRelationNode($node, $addedColumns, $definition);
        }

        return false;
    }

    /**
     * @param NameNode $node
     * @param array $addedColumns
     * @param array $definition
     * @return bool
     */
    protected function convertNameNode(NameNode $node, array &$addedColumns, array &$definition)
    {
        if ($node->getContainer() === PriceList::class) {
            $priceListKey = 'pricelist|' . (int)$node->getContainerId();
            if (empty($addedColumns[$priceListKey])) {
                $definition['price_lists'][] = (int)$node->getContainerId();
                $addedColumns[$priceListKey] = true;
            }

            return true;
        }

        return false;
    }

    /**
     * @param RelationNode $node
     * @param array $addedColumns
     * @param array $definition
     * @return bool
     */
    protected function convertRelationNode(RelationNode $node, array &$addedColumns, array &$definition)
    {
        if ($node->getContainer() === PriceList::class && $node->getField() === 'prices') {
            $pricesKey = 'price|' . $node->getContainerId();
            if (empty($addedColumns[$pricesKey])) {
                $definition['prices'][] = $node->getContainerId();
                $addedColumns[$pricesKey] = true;
            }

            return true;
        }

        return false;
    }
}
