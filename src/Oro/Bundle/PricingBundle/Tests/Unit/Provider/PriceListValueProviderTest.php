<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Provider\PriceListValueProvider;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PriceListValueProviderTest extends TestCase
{
    private ShardManager|MockObject $shardManager;

    private ManagerRegistry|MockObject $doctrine;

    private AclHelper|MockObject $aclHelper;

    private PriceListValueProvider $priceListValueProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->priceListValueProvider = new PriceListValueProvider(
            $this->shardManager,
            $this->doctrine,
            $this->aclHelper
        );
    }

    public function testGetPriceListIdWhenShardingDisabled(): void
    {
        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->aclHelper->expects(self::never())
            ->method('apply');

        $priceListId = $this->priceListValueProvider->getPriceListId();
        self::assertNull($priceListId);
    }

    public function testGetPriceListIdWhenShardingEnabled(): void
    {
        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(true);

        $defaultPriceListId = 1;

        $repo = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('orderBy')
            ->with('p.id')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('select')
            ->with('p.id')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn($defaultPriceListId);

        $priceListId = $this->priceListValueProvider->getPriceListId();
        self::assertSame($defaultPriceListId, $priceListId);
    }
}
