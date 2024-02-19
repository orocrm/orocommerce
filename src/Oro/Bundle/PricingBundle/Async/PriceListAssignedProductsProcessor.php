<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Provider\DependentPriceListProvider;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Updates combined price lists in case of price list product assigned rule is changed.
 */
class PriceListAssignedProductsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private LoggerInterface $logger;
    private PriceListProductAssignmentBuilder $assignmentBuilder;
    private ManagerRegistry $doctrine;
    private NotificationAlertManager $notificationAlertManager;
    private PriceListTriggerHandler $triggerHandler;
    private DependentPriceListProvider $dependentPriceListProvider;
    private array $processedPriceListIds = [];

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        PriceListProductAssignmentBuilder $assignmentBuilder,
        NotificationAlertManager $notificationAlertManager,
        PriceListTriggerHandler $triggerHandler
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->assignmentBuilder = $assignmentBuilder;
        $this->notificationAlertManager = $notificationAlertManager;
        $this->triggerHandler = $triggerHandler;
    }

    public function setDependentPriceListProvider(DependentPriceListProvider $dependentPriceListProvider): void
    {
        $this->dependentPriceListProvider = $dependentPriceListProvider;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [ResolvePriceListAssignedProductsTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $message->getBody();
        $priceListsCount = count($body['product']);
        $this->processedPriceListIds = [];

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(PriceList::class);
        foreach ($body['product'] as $priceListId => $productIds) {
            /** @var PriceList|null $priceList */
            $priceList = $em->find(PriceList::class, $priceListId);
            if (null === $priceList) {
                $this->logger->warning(sprintf(
                    'PriceList entity with identifier %s not found.',
                    $priceListId
                ));
                continue;
            }

            $em->beginTransaction();
            try {
                $this->processPriceList($priceList, $productIds);

                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
                $this->logger->error(
                    'Unexpected exception occurred during Price List Assigned Products build.',
                    ['exception' => $e]
                );

                if ($e instanceof RetryableException) {
                    // On RetryableException send back to queue the message related to a single price list
                    // that triggered an exception.
                    // If this was the only one PL in the message REQUEUE it to persist retries counter
                    if ($priceListsCount === 1) {
                        return self::REQUEUE;
                    }

                    $this->triggerHandler->handlePriceListTopic(
                        ResolvePriceListAssignedProductsTopic::getName(),
                        $priceList,
                        $productIds
                    );
                } else {
                    $this->notificationAlertManager->addNotificationAlert(
                        PriceListCalculationNotificationAlert::createForAssignedProductsBuildError(
                            $priceListId,
                            $e->getMessage()
                        )
                    );
                    if ($priceListsCount === 1) {
                        return self::REJECT;
                    }
                }
            }
        }

        return self::ACK;
    }

    private function processPriceList(PriceList $priceList, array $productIds): void
    {
        if (!empty($this->processedPriceListIds[$priceList->getId()])) {
            return;
        }

        $this->notificationAlertManager->resolveNotificationAlertByOperationAndItemIdForCurrentUser(
            PriceListCalculationNotificationAlert::OPERATION_ASSIGNED_PRODUCTS_BUILD,
            $priceList->getId()
        );

        $this->assignmentBuilder->buildByPriceList($priceList, $productIds);
        $this->processedPriceListIds[$priceList->getId()] = true;

        foreach ($this->dependentPriceListProvider->getDirectlyDependentPriceLists($priceList) as $dependentPriceList) {
            if ($dependentPriceList->getProductAssignmentRule()) {
                $this->processPriceList($dependentPriceList, $productIds);
            }
        }
    }
}
