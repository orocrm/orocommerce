<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchDeleteTopic;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes from the website search index the specified entities by class and ids.
 */
class WebsiteSearchDeleteProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use WebsiteSearchEngineExceptionAwareProcessorTrait;
    use LoggerAwareTrait;

    private IndexerInterface $indexer;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(IndexerInterface $indexer, EventDispatcherInterface $eventDispatcher)
    {
        $this->indexer = $indexer;
        $this->eventDispatcher = $eventDispatcher;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [WebsiteSearchDeleteTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();

        return $this->doProcess(
            function () use ($messageBody) {
                $this->indexer->delete($messageBody['entity'], $messageBody['context']);

                return self::ACK;
            },
            $this->eventDispatcher,
            $this->logger
        );
    }
}
