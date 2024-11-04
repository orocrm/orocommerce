<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener\CombinedPriceListAssociation;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\GetAssociatedWebsitesEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\ProcessEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerGroupCPLUpdateEvent;
use Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation\ProcessAssociationCustomerGroupEventListener;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Resolver\ActiveCombinedPriceListResolver;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ProcessAssociationCustomerGroupEventListenerTest extends TestCase
{
    use EntityTrait;

    private EventDispatcherInterface|MockObject $eventDispatcher;
    private ManagerRegistry|MockObject $registry;
    private ActiveCombinedPriceListResolver|MockObject $activeCombinedPriceListResolver;
    private CombinedPriceListTriggerHandler|MockObject $triggerHandler;
    private ProcessAssociationCustomerGroupEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->activeCombinedPriceListResolver = $this->createMock(ActiveCombinedPriceListResolver::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);

        $this->listener = new ProcessAssociationCustomerGroupEventListener(
            $this->eventDispatcher,
            $this->registry,
            $this->activeCombinedPriceListResolver,
            $this->triggerHandler
        );
    }

    public function testOnProcessAssociationsSkippedEvent()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $associations = ['config' => true];
        $processEvent = new ProcessEvent($cpl, $associations, 100);

        $this->activeCombinedPriceListResolver->expects($this->never())
            ->method($this->anything());
        $this->eventDispatcher->expects($this->never())
            ->method($this->anything());

        $this->listener->onProcessAssociations($processEvent);
    }

    /**
     * @dataProvider skipDataProvider
     */
    public function testOnProcessAssociations(bool $isSkipNotifications)
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 10]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 100]);
        $associations = [
            'website' => [
                'id:10' => [
                    'customer_group' => ['ids' => [100]]
                ]
            ]
        ];
        $processEvent = new ProcessEvent($cpl, $associations, 100, $isSkipNotifications);

        $websiteRepo = $this->createMock(WebsiteRepository::class);
        $websiteRepo->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                return $this->getEntity(Website::class, ['id' => $id]);
            });

        $relation = new CombinedPriceListToCustomerGroup();
        $relation->setFullChainPriceList($cpl);
        $relation->setPriceList($cpl);
        $relation->setWebsite($website);
        $relation->setCustomerGroup($customerGroup);
        $cplRepo = $this->createMock(CombinedPriceListRepository::class);
        $cplRepo->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($cpl, $cpl, $website, $this->isType('int'), $customerGroup)
            ->willReturn($relation);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnCallback(function ($className) use ($cplRepo) {
                if ($className === CombinedPriceList::class) {
                    return $cplRepo;
                }

                $repo = $this->createMock(EntityRepository::class);
                $repo->expects($this->any())
                    ->method('find')
                    ->willReturnCallback(function ($id) use ($className) {
                        return $this->getEntity($className, ['id' => $id]);
                    });

                return $repo;
            });

        $this->activeCombinedPriceListResolver->expects($this->once())
            ->method('getActiveCplByFullCPL')
            ->with($cpl)
            ->willReturn($cpl);

        $this->eventDispatcher->expects($isSkipNotifications ? $this->never() : $this->once())
            ->method('dispatch')
            ->with(
                new CustomerGroupCPLUpdateEvent([
                    [
                        'websiteId' => 10,
                        'customerGroups' => [100]
                    ]
                ]),
                CustomerGroupCPLUpdateEvent::NAME
            );

        $this->listener->onProcessAssociations($processEvent);
    }

    public function skipDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    public function testOnProcessAssociationsWebsitesNotFound()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $associations = [
            'website' => [
                'id:10' => [
                    'customer_group' => ['ids' => [100]]
                ]
            ]
        ];
        $processEvent = new ProcessEvent($cpl, $associations, 100);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $this->activeCombinedPriceListResolver->expects($this->never())
            ->method('getActiveCplByFullCPL');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->listener->onProcessAssociations($processEvent);
    }

    public function testOnGetAssociatedWebsites()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 42]);

        $event = new GetAssociatedWebsitesEvent($cpl);

        $repo = $this->createMock(CombinedPriceListToCustomerGroupRepository::class);
        $repo->expects($this->once())
            ->method('getWebsitesByCombinedPriceList')
            ->with($cpl)
            ->willReturn([$website]);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListToCustomerGroup::class)
            ->willReturn($repo);

        $this->listener->onGetAssociatedWebsites($event);
        $this->assertEquals([42 => $website], $event->getWebsites());
    }

    public function testOnGetAssociatedWebsitesWhenAssociationsProvided()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $associations = [
            'website' => [
                'id:10' => [
                    'customer' => ['ids' => [100]]
                ]
            ]
        ];
        $event = new GetAssociatedWebsitesEvent($cpl, $associations);

        $this->registry->expects($this->never())
            ->method('getRepository')
            ->with(CombinedPriceListToCustomerGroup::class);

        $this->listener->onGetAssociatedWebsites($event);
        $this->assertEmpty($event->getWebsites());
    }
}
