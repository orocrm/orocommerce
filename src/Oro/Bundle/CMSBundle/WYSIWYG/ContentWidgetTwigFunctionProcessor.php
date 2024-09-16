<?php

namespace Oro\Bundle\CMSBundle\WYSIWYG;

use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;

/**
 * Save uses of the content widgets in WYSIWYG fields
 */
class ContentWidgetTwigFunctionProcessor implements WYSIWYGTwigFunctionProcessorInterface
{
    private ContentWidgetProvider $contentWidgetProvider;

    public function __construct(ContentWidgetProvider $contentWidgetProvider)
    {
        $this->contentWidgetProvider = $contentWidgetProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getApplicableMapping(): array
    {
        return [
            self::FIELD_CONTENT_TYPE => ['widget'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function processTwigFunctions(WYSIWYGProcessedDTO $processedDTO, array $twigFunctionCalls): bool
    {
        $ownerEntityId = $processedDTO->requireOwnerEntityId();
        if (!\is_numeric($ownerEntityId)) {
            return false;
        }

        $ownerEntityId = (int)$ownerEntityId;
        $ownerEntityClass = $processedDTO->requireOwnerEntityClass();
        $ownerEntityField = $processedDTO->requireOwnerEntityFieldName();

        $actualWidgetCalls = $this->getWidgetNames($twigFunctionCalls);

        $em = $processedDTO->getProcessedEntity()->getEntityManager();
        $currentUsages = $em->getRepository(ContentWidgetUsage::class)->findForEntityField(
            $ownerEntityClass,
            $ownerEntityId,
            $ownerEntityField
        );

        $isFlushNeeded = false;
        // Removing currently not used widgets
        foreach ($currentUsages as $usage) {
            $widgetName = $usage->getContentWidget()->getName();
            if (!isset($actualWidgetCalls[$widgetName])) {
                $em->remove($usage);
                $isFlushNeeded = true;
            } else {
                unset($actualWidgetCalls[$widgetName]);
            }
        }

        // Adding new widget usages
        if ($actualWidgetCalls) {
            $contentWidgets = $this->contentWidgetProvider->getContentWidgets(
                $processedDTO,
                array_keys($actualWidgetCalls)
            );
            foreach ($contentWidgets as $contentWidget) {
                $usage = new ContentWidgetUsage();
                $usage->setContentWidget($contentWidget);
                $usage->setEntityClass($ownerEntityClass);
                $usage->setEntityId($ownerEntityId);
                $usage->setEntityField($ownerEntityField);

                $em->persist($usage);
                $isFlushNeeded = true;
            }
        }

        return $isFlushNeeded;
    }

    /**
     * {@inheritDoc}
     */
    public function onPreRemove(WYSIWYGProcessedDTO $processedDTO): bool
    {
        $ownerEntityId = $processedDTO->requireOwnerEntityId();
        if (!\is_numeric($ownerEntityId)) {
            return false;
        }

        $em = $processedDTO->getProcessedEntity()->getEntityManager();
        $currentUsages = $em->getRepository(ContentWidgetUsage::class)->findForEntityField(
            $processedDTO->requireOwnerEntityClass(),
            (int)$ownerEntityId
        );
        if (!$currentUsages) {
            return false;
        }

        foreach ($currentUsages as $usage) {
            $em->remove($usage);
        }

        return true;
    }

    /**
     * @param array $twigFunctionCalls
     *
     * @return array [widget name => true, ...]
     */
    private function getWidgetNames(array $twigFunctionCalls): array
    {
        $actualWidgetCalls = [];
        if (isset($twigFunctionCalls[self::FIELD_CONTENT_TYPE]['widget'])) {
            foreach ($twigFunctionCalls[self::FIELD_CONTENT_TYPE]['widget'] as [$widgetName]) {
                if ($widgetName && \is_string($widgetName)) {
                    $actualWidgetCalls[$widgetName] = true;
                }
            }
        }

        return $actualWidgetCalls;
    }
}
