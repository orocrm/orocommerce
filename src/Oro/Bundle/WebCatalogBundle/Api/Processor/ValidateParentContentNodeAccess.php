<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityAccess;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\WebCatalogBundle\Api\Repository\ContentNodeRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Loads the parent web catalog content node from the database
 * and checks whether an access to it is granted.
 */
class ValidateParentContentNodeAccess implements ProcessorInterface
{
    /** @var ContentNodeRepository */
    private $contentNodeRepository;

    /**
     * @param ContentNodeRepository $contentNodeRepository
     */
    public function __construct(ContentNodeRepository $contentNodeRepository)
    {
        $this->contentNodeRepository = $contentNodeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        try {
            $parentNode = $this->contentNodeRepository->getContentNodeEntity($context->getParentId());
        } catch (AccessDeniedException $e) {
            throw new AccessDeniedException('No access to the parent entity.', $e);
        }
        if (null === $parentNode) {
            throw new NotFoundHttpException('The parent entity does not exist.');
        }

        $context->setProcessed(ValidateParentEntityAccess::OPERATION_NAME);
    }
}
