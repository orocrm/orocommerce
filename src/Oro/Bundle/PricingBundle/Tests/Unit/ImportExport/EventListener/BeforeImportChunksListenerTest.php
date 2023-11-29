<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Event\BeforeImportChunksEvent;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\ImportExport\EventListener\BeforeImportChunksListener;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class BeforeImportChunksListenerTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry|MockObject $doctrine;

    private ShardManager|MockObject $shardManager;

    private EntityRepository|MockObject $priceListRepository;

    private ProductPriceRepository|MockObject $productPriceRepository;

    private PriceListToProductRepository|MockObject $pl2pRepository;

    private BeforeImportChunksListener $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->priceListRepository = $this->createMock(EntityRepository::class);
        $this->productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $this->pl2pRepository = $this->createMock(PriceListToProductRepository::class);

        $this->listener = new BeforeImportChunksListener($this->registry, $this->shardManager);
    }

    /**
     * @dataProvider onBeforeImportChunksWithoutPriceListDataProvider
     */
    public function testOnBeforeImportChunksValidation(array $body): void
    {
        $event = new BeforeImportChunksEvent($body);

        $this->registry
            ->expects($this->never())
            ->method('getRepository')
            ->with(PriceList::class);
        $this->registry
            ->expects($this->never())
            ->method('getRepository')
            ->with(ProductPrice::class);

        $this->listener->onBeforeImportChunks($event);
    }

    /**
     * @return array
     */
    public function onBeforeImportChunksWithoutPriceListDataProvider(): array
    {
        return [
            'empty processor alias' => [
                'body' => [],
            ],
            'unsupported processor alias' => [
                'body' => [
                    'processorAlias' => 'unsupportedAlias',
                ],
            ],
            'no price list id' => [
                'body' => [
                    'processorAlias' => BeforeImportChunksListener::RESET_PROCESSOR_ALIAS,
                ],
            ],
            'invalid process type' => [
                'body' => [
                    'processorAlias' => BeforeImportChunksListener::RESET_PROCESSOR_ALIAS,
                    'options' => ['price_list_id' => 1],
                    'process' => ProcessorRegistry::TYPE_IMPORT_VALIDATION
                ],
            ],
        ];
    }

    public function testOnBeforeImportChunksNonExistentPriceList(): void
    {
        $body['process'] = ProcessorRegistry::TYPE_IMPORT;
        $body['processorAlias'] = BeforeImportChunksListener::RESET_PROCESSOR_ALIAS;
        $body['options']['price_list_id'] = 15;
        $event = new BeforeImportChunksEvent($body);

        $this->registry
            ->expects($this->once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($this->priceListRepository);

        $this->priceListRepository
            ->expects($this->once())
            ->method('find')
            ->with(15)
            ->willReturn(null);

        $this->listener->onBeforeImportChunks($event);
    }

    public function testOnBeforeImportChunks(): void
    {
        $body['process'] = ProcessorRegistry::TYPE_IMPORT;
        $body['processorAlias'] = BeforeImportChunksListener::RESET_PROCESSOR_ALIAS;
        $body['options']['price_list_id'] = 16;
        $event = new BeforeImportChunksEvent($body);

        $priceList = $this->getEntity(PriceList::class, ['id' => 16]);

        $this->registry->expects($this->exactly(3))
            ->method('getRepository')
            ->withConsecutive([PriceList::class], [ProductPrice::class])
            ->willReturnOnConsecutiveCalls(
                $this->priceListRepository,
                $this->productPriceRepository,
                $this->pl2pRepository
            );

        $this->priceListRepository->expects($this->once())
            ->method('find')
            ->with(16)
            ->willReturn($priceList);

        $this->productPriceRepository->expects($this->once())
            ->method('deleteByPriceList')
            ->with($this->shardManager, $priceList);

        $this->pl2pRepository->expects($this->once())
            ->method('deleteManualRelations')
            ->with($priceList);

        $this->listener->onBeforeImportChunks($event);
    }
}
