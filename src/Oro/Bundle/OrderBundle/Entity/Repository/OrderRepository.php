<?php

namespace Oro\Bundle\OrderBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResetCustomerUserTrait;
use Oro\Bundle\CustomerBundle\Entity\Repository\ResettableCustomerUserRepositoryInterface;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Repository for Order entity provides methods to extract order related info.
 */
class OrderRepository extends ServiceEntityRepository implements ResettableCustomerUserRepositoryInterface
{
    use ResetCustomerUserTrait;

    private const AMOUNT_TYPE_SUBTOTAL_WITH_DISCOUNT = 'subtotal_with_discounts';
    private const AMOUNT_TYPE_SUBTOTAL = 'subtotal';
    private const AMOUNT_TYPE_TOTAL = 'total';

    private ?AclHelper $aclHelper = null;
    private ?DateHelper $dateHelper = null;

    public function setAclHelper(AclHelper $aclHelper): void
    {
        $this->aclHelper = $aclHelper;
    }

    public function setDateHelper(DateHelper $dateHelper): void
    {
        $this->dateHelper = $dateHelper;
    }

    public function hasRecordsWithRemovingCurrencies(
        array $removingCurrencies,
        Organization $organization = null
    ): bool {
        $qb = $this->createQueryBuilder('orders');
        $qb
            ->select('COUNT(orders.id)')
            ->where($qb->expr()->in('orders.currency', ':removingCurrencies'))
            ->setParameter('removingCurrencies', $removingCurrencies);

        if (null !== $organization) {
            $qb
                ->andWhere(($qb->expr()->in('orders.organization', ':organization')))
                ->setParameter('organization', $organization);
        }

        return (bool)$qb->getQuery()->getSingleScalarResult();
    }

    public function getOrderWithRelations(int $id): ?Order
    {
        $qb = $this->createQueryBuilder('orders');
        $qb->select('orders, lineItems, shippingAddress, billingAddress, discounts')
            ->leftJoin('orders.lineItems', 'lineItems')
            ->leftJoin('orders.shippingAddress', 'shippingAddress')
            ->leftJoin('orders.billingAddress', 'billingAddress')
            ->leftJoin('orders.discounts', 'discounts')
            ->where($qb->expr()->eq('orders.id', ':orderId'))
            ->setParameter('orderId', $id)
            ->addOrderBy($qb->expr()->asc('orders.id'))
            ->addOrderBy($qb->expr()->asc('lineItems.id'));

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getLatestOrderedProductsInfo(
        array $productIds,
        int $websiteId,
        array $orderStatuses
    ): QueryBuilder {
        $queryBuilder = $this->getBaseLatestOrderedProductsQueryBuilder($websiteId, $orderStatuses);
        $queryBuilder
            ->addSelect('IDENTITY(lineItems.product) as product_id')
            ->andWhere($queryBuilder->expr()->in('lineItems.product', ':productIdList'))
            ->andWhere($queryBuilder->expr()->isNull('lineItems.parentProduct'))
            ->andWhere($queryBuilder->expr()->isNotNull('orders.customerUser'))
            ->addGroupBy('lineItems.product')
            ->orderBy('lineItems.product')
            ->setParameter('productIdList', $productIds);

        return $queryBuilder;
    }

    public function getLatestOrderedParentProductsInfo(
        array $productIds,
        int $websiteId,
        array $orderStatuses
    ): QueryBuilder {
        $queryBuilder = $this->getBaseLatestOrderedProductsQueryBuilder($websiteId, $orderStatuses);
        $queryBuilder
            ->addSelect('IDENTITY(lineItems.parentProduct) as product_id')
            ->andWhere($queryBuilder->expr()->in('lineItems.parentProduct', ':productIdList'))
            ->andWhere($queryBuilder->expr()->isNotNull('orders.customerUser'))
            ->addGroupBy('lineItems.parentProduct')
            ->orderBy('lineItems.parentProduct')
            ->setParameter('productIdList', $productIds);

        return $queryBuilder;
    }

    private function getBaseLatestOrderedProductsQueryBuilder(int $websiteId, array $orderStatuses): QueryBuilder
    {
        $qb = $this->createQueryBuilder('orders');
        $qb
            ->select('IDENTITY(orders.customerUser) as customer_user_id')
            ->addSelect(
                $qb->expr()->max('orders.createdAt') . ' as created_at'
            )
            ->innerJoin('orders.lineItems', 'lineItems')
            ->andWhere($qb->expr()->eq('orders.website', ':websiteId'))
            ->andWhere($qb->expr()->in('orders.internal_status', ':orderStatuses'))
            ->groupBy('orders.customerUser');

        $qb
            ->setParameter('orderStatuses', $orderStatuses)
            ->setParameter('websiteId', $websiteId);

        return $qb;
    }

    /**
     * @return array<array{
     *     number: int,
     *     yearCreated?: string,
     *     monthCreated?: string,
     *     weekCreated?: string,
     *     dayCreated?: string,
     *     dateCreated?: string,
     *     hourCreated?: string
     * }>
     */
    public function getSalesOrdersNumber(
        \DateTime $dateTimeFrom,
        ?\DateTime $dateTimeTo,
        ?array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $scaleType
    ): array {
        $qb = $this->getSalesOrdersNumberQueryBuilder(
            $dateTimeFrom,
            $dateTimeTo,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $scaleType
        );

        return $this->aclHelper->apply($qb)->getResult();
    }

    public function getSalesOrdersNumberQueryBuilder(
        \DateTime $dateTimeFrom,
        ?\DateTime $dateTimeTo,
        ?array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $scaleType
    ): QueryBuilder {
        $queryBuilder = $this->createQueryBuilder('o');
        $queryBuilder->select('COUNT(o.id) AS number');

        return $this->getSalesOrdersDataQueryBuilder(
            $queryBuilder,
            $dateTimeFrom,
            $dateTimeTo,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $scaleType
        );
    }

    public function getSalesOrdersDataQueryBuilder(
        QueryBuilder $queryBuilder,
        \DateTime $dateTimeFrom,
        ?\DateTime $dateTimeTo,
        ?array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $scaleType
    ): QueryBuilder {
        $dateTimeFrom = clone $dateTimeFrom;

        if (null !== $includedOrderStatuses) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in('o.internal_status', ':includedOrderStatuses'))
                ->setParameter('includedOrderStatuses', $includedOrderStatuses);
        }

        if (!$isIncludeSubOrders) {
            $queryBuilder->andWhere($queryBuilder->expr()->isNull('o.parent'));
        }

        $this->dateHelper->addDatePartsSelect(
            clone $dateTimeFrom,
            null === $dateTimeTo ? new \DateTime('now', new \DateTimeZone('UTC')) : clone $dateTimeTo,
            $queryBuilder,
            'o.createdAt',
            $scaleType
        );

        if (null === $dateTimeTo) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->gte('o.createdAt', ':from'))
                ->setParameter('from', $dateTimeFrom, Types::DATETIME_MUTABLE);
        } else {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->between('o.createdAt', ':from', ':to'))
                ->setParameter('from', $dateTimeFrom, Types::DATETIME_MUTABLE)
                ->setParameter('to', $dateTimeTo, Types::DATETIME_MUTABLE);
        }

        return $queryBuilder;
    }

    /**
     * @return array<array{
     *     amount: string,
     *     yearCreated?: string,
     *     monthCreated?: string,
     *     weekCreated?: string,
     *     dayCreated?: string,
     *     dateCreated?: string,
     *     hourCreated?: string
     * }>
     */
    public function getSalesOrdersVolume(
        \DateTime $dateTimeFrom,
        ?\DateTime $dateTimeTo,
        ?array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $amountType,
        string $currency,
        string $scaleType
    ): array {
        $queryBuilder = $this->getSalesOrdersVolumeQueryBuilder(
            $dateTimeFrom,
            $dateTimeTo,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $amountType,
            $currency,
            $scaleType
        );

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    public function getSalesOrdersVolumeQueryBuilder(
        \DateTime $dateTimeFrom,
        ?\DateTime $dateTimeTo,
        ?array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $amountType,
        string $currency,
        string $scaleType
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('o');
        switch ($amountType) {
            case self::AMOUNT_TYPE_SUBTOTAL_WITH_DISCOUNT:
                $qb->select(
                    'SUM(
                        CASE WHEN o.subtotalWithDiscounts IS NOT NULL THEN o.subtotalWithDiscounts ELSE 0 END
                    ) AS amount'
                );
                break;
            case self::AMOUNT_TYPE_SUBTOTAL:
                $qb->select(
                    'SUM(
                        CASE WHEN o.subtotalValue IS NOT NULL THEN o.subtotalValue ELSE 0 END
                    ) AS amount'
                );
                break;
            case self::AMOUNT_TYPE_TOTAL:
                $qb->select(
                    'SUM(
                        CASE WHEN o.totalValue IS NOT NULL THEN o.totalValue ELSE 0 END
                    ) AS amount'
                );
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported amount type "%s"', $amountType));
        }

        $salesOrdersDataQueryBuilder = $this->getSalesOrdersDataQueryBuilder(
            $qb,
            $dateTimeFrom,
            $dateTimeTo,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $scaleType
        );

        switch ($amountType) {
            case self::AMOUNT_TYPE_SUBTOTAL_WITH_DISCOUNT:
            case self::AMOUNT_TYPE_SUBTOTAL:
                $salesOrdersDataQueryBuilder
                    ->andWhere(
                        $salesOrdersDataQueryBuilder->expr()->eq('o.subtotalCurrency', ':currency')
                    )
                    ->setParameter('currency', $currency);
                break;
            case self::AMOUNT_TYPE_TOTAL:
                $salesOrdersDataQueryBuilder
                    ->andWhere(
                        $salesOrdersDataQueryBuilder->expr()->eq('o.totalCurrency', ':currency')
                    )
                    ->setParameter('currency', $currency);
                break;
        }

        return $salesOrdersDataQueryBuilder;
    }
}
