<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\PricingBundle\Async\SingleCplProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListActivationStatusHelperInterface;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SingleCplProcessorTest extends TestCase
{
    use EntityTrait;

    /**
     * @var JobRunner|MockObject
     */
    private $jobRunner;

    /**
     * @var ManagerRegistry|MockObject
     */
    private $doctrine;

    /**
     * @var CombinedPriceListsBuilderFacade|MockObject
     */
    private $combinedPriceListsBuilderFacade;

    /**
     * @var CombinedPriceListTriggerHandler|MockObject
     */
    private $indexationTriggerHandler;

    /**
     * @var CombinedPriceListActivationStatusHelperInterface|MockObject
     */
    private $activationStatusHelper;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private $dispatcher;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var CombinedPriceListScheduleResolver
     */
    private $scheduleResolver;

    /**
     * @var SingleCplProcessor
     */
    private SingleCplProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->combinedPriceListsBuilderFacade = $this->createMock(CombinedPriceListsBuilderFacade::class);
        $this->indexationTriggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);
        $this->activationStatusHelper = $this->createMock(CombinedPriceListActivationStatusHelperInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->scheduleResolver = $this->createMock(CombinedPriceListScheduleResolver::class);

        $this->processor = new SingleCplProcessor(
            $this->jobRunner,
            $this->doctrine,
            $this->combinedPriceListsBuilderFacade,
            $this->indexationTriggerHandler,
            $this->activationStatusHelper,
            $this->dispatcher
        );
        $this->processor->setLogger($this->logger);
        $this->processor->setScheduleResolver($this->scheduleResolver);

        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);
        $this->processor->setTopic(new CombineSingleCombinedPriceListPricesTopic($this->combinedPriceListProvider));
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [CombineSingleCombinedPriceListPricesTopic::getName()],
            SingleCplProcessor::getSubscribedTopics()
        );
    }

    public function testProcessUnexpectedException()
    {
        $this->assertCombinedPriceListProviderCalls();
        $body = JSON::encode([
            'cpl' => 1,
            'jobId' => 100,
            'products' => []
        ]);
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        $e = new \Exception();
        $this->assertJobRunnerCalls();
        $this->assertTransactionRollback();

        $this->activationStatusHelper->expects($this->any())
            ->method('isReadyForBuild')
            ->willReturn(true);
        $this->combinedPriceListsBuilderFacade->expects($this->any())
            ->method('rebuildWithoutTriggers')
            ->willThrowException($e);
        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Combined Price Lists build.',
                [
                    'topic' => CombineSingleCombinedPriceListPricesTopic::getName(),
                    'message' => $body,
                    'exception' => $e
                ]
            );

        $this->assertEquals(
            $this->processor::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessUnexpectedDatabaseException()
    {
        $this->assertCombinedPriceListProviderCalls();
        $body = JSON::encode([
            'cpl' => 1,
            'jobId' => 100,
            'products' => []
        ]);
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        $e = $this->createMock(ForeignKeyConstraintViolationException::class);
        $this->assertJobRunnerCalls();
        $this->assertTransactionRollback();

        $this->activationStatusHelper->expects($this->any())
            ->method('isReadyForBuild')
            ->willReturn(true);
        $this->combinedPriceListsBuilderFacade->expects($this->any())
            ->method('rebuildWithoutTriggers')
            ->willThrowException($e);
        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Unexpected retryable database exception occurred during Combined Price Lists build.',
                [
                    'topic' => CombineSingleCombinedPriceListPricesTopic::getName(),
                    'message' => $body,
                    'exception' => $e
                ]
            );

        $this->assertEquals(
            $this->processor::REQUEUE,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessUnexpectedDatabaseExceptionDuringMessageResolve()
    {
        $body = JSON::encode([
            'cpl' => 1,
            'jobId' => 100,
            'products' => []
        ]);
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        $e = $this->createMock(ForeignKeyConstraintViolationException::class);

        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCombinedPriceListById')
            ->willThrowException($e);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Unexpected retryable database exception occurred during Combined Price Lists build.',
                [
                    'topic' => CombineSingleCombinedPriceListPricesTopic::getName(),
                    'message' => $body,
                    'exception' => $e
                ]
            );

        $this->assertEquals(
            $this->processor::REQUEUE,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessUnexpectedRetryableException()
    {
        $this->assertCombinedPriceListProviderCalls();
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode([
                'cpl' => 1,
                'jobId' => 100,
                'products' => []
            ]));

        $e = $this->createMock(DeadlockException::class);
        $this->assertJobRunnerCalls();
        $this->assertTransactionRollback();

        $this->activationStatusHelper->expects($this->any())
            ->method('isReadyForBuild')
            ->willReturn(true);
        $this->combinedPriceListsBuilderFacade->expects($this->any())
            ->method('rebuildWithoutTriggers')
            ->willThrowException($e);
        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->logger->expects($this->never())
            ->method('error');

        $this->assertEquals(
            $this->processor::REQUEUE,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    /**
     * @dataProvider productsDataProvider
     */
    public function testProcess(array $products)
    {
        $this->assertCombinedPriceListProviderCalls();
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $assignTo = ['config' => true];
        $message = $this->getMessage($products, $assignTo);

        $this->assertJobRunnerCalls();
        $this->assertActivityRecordsRemoval($products, $cpl);
        $this->assertTransactionCommit();

        $this->activationStatusHelper->expects($this->once())
            ->method('isReadyForBuild')
            ->with($cpl)
            ->willReturn(true);
        $this->assertCplBuild($cpl, $products, $assignTo);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('processAssignments')
            ->with($cpl, $assignTo);

        $this->logger->expects($this->never())
            ->method('error');

        $this->assertEquals(
            $this->processor::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessOfNotReadyCpl()
    {
        $products = [];
        $this->assertCombinedPriceListProviderCalls();
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $assignTo = ['config' => true];
        $message = $this->getMessage($products, $assignTo);

        $this->assertJobRunnerCalls();
        $this->assertActivityRecordsRemoval($products, $cpl);
        $this->assertTransactionCommit();

        $this->activationStatusHelper->expects($this->once())
            ->method('isReadyForBuild')
            ->with($cpl)
            ->willReturn(false);
        $activeCpl = $this->getEntity(CombinedPriceList::class, ['id' => 2]);
        $this->scheduleResolver->expects($this->once())
            ->method('getActiveCplByFullCPL')
            ->with($cpl)
            ->willReturn($activeCpl);
        $this->assertCplBuild($activeCpl, $products, $assignTo);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('processAssignments')
            ->with($cpl, $assignTo);

        $this->logger->expects($this->never())
            ->method('error');

        $this->assertEquals(
            $this->processor::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    /**
     * @dataProvider productsDataProvider
     */
    public function testProcessNotReadyCpl(array $products)
    {
        $this->assertCombinedPriceListProviderCalls();
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $assignTo = ['config' => true];
        $message = $this->getMessage($products, $assignTo);

        $this->assertJobRunnerCalls();
        $this->assertActivityRecordsRemoval($products, $cpl);
        $this->assertTransactionCommit();

        $this->activationStatusHelper->expects($this->once())
            ->method('isReadyForBuild')
            ->with($cpl)
            ->willReturn(false);
        $this->combinedPriceListsBuilderFacade->expects($this->never())
            ->method('rebuildWithoutTriggers');
        $this->combinedPriceListsBuilderFacade->expects($this->never())
            ->method('triggerProductIndexation');
        $this->dispatcher->expects($this->never())
            ->method('dispatch')
            ->with(
                new CombinedPriceListsUpdateEvent([$cpl->getId()]),
                CombinedPriceListsUpdateEvent::NAME
            );
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('processAssignments')
            ->with($cpl, $assignTo);

        $this->logger->expects($this->never())
            ->method('error');

        $this->assertEquals(
            $this->processor::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function productsDataProvider(): array
    {
        return [
            [[]],
            [[1, 2]]
        ];
    }

    private function assertActivityRecordsRemoval(array $products, CombinedPriceList $cpl): void
    {
        $repo = $this->createMock(CombinedPriceListBuildActivityRepository::class);
        if ($products) {
            $repo->expects($this->once())
                ->method('deleteActivityRecordsForJob')
                ->with(42);
        } else {
            $repo->expects($this->once())
                ->method('deleteActivityRecordsForCombinedPriceList')
                ->with($cpl);
        }
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListBuildActivity::class)
            ->willReturn($repo);
    }

    private function assertCombinedPriceListProviderCalls(): void
    {
        $this->combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceListById')
            ->willReturn($this->getEntity(CombinedPriceList::class, ['id' => 1]));
        $this->combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceListByCollectionInformation')
            ->willReturn($this->getEntity(CombinedPriceList::class, ['id' => 1]));
    }

    private function assertCplBuild(CombinedPriceList $cpl, array $products, array $assignTo): void
    {
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuildWithoutTriggers')
            ->with([$cpl], $products);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('triggerProductIndexation')
            ->with($cpl, $assignTo, $products);
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                new CombinedPriceListsUpdateEvent([$cpl->getId()]),
                CombinedPriceListsUpdateEvent::NAME
            );
    }

    private function assertJobRunnerCalls(): void
    {
        $rootJob = $this->createMock(Job::class);
        $rootJob->expects($this->any())
            ->method('getId')
            ->willReturn(42);
        $job = $this->createMock(Job::class);
        $job->expects($this->any())
            ->method('getRootJob')
            ->willReturn($rootJob);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willReturnCallback(
                function ($ownerId, $closure) use ($job) {
                    return $closure($this->jobRunner, $job);
                }
            );
    }

    private function getMessage(array $products, array $assignTo): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode([
                'cpl' => 1,
                'jobId' => 100,
                'products' => $products,
                'assign_to' => $assignTo
            ]));

        return $message;
    }

    private function assertTransactionRollback(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('rollback');

        $this->indexationTriggerHandler->expects($this->once())
            ->method('startCollectVersioned');
        $this->indexationTriggerHandler->expects($this->once())
            ->method('rollback');
    }

    private function assertTransactionCommit(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');
        $em->expects($this->never())
            ->method('rollback');

        $this->indexationTriggerHandler->expects($this->once())
            ->method('startCollectVersioned');
        $this->indexationTriggerHandler->expects($this->once())
            ->method('commit');
        $this->indexationTriggerHandler->expects($this->never())
            ->method('rollback');
    }
}
