<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Menu;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader\ResolvedContentNodesLoader;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Menu\MenuContentNodesProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub\ContentNodeStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuContentNodesProviderTest extends TestCase
{
    private MenuContentNodesProvider $provider;

    private ResolvedContentNodesLoader|MockObject $resolvedContentNodesLoader;

    private AuthorizationCheckerInterface|MockObject $authorizationChecker;

    private ContentNodeRepository|MockObject $repository;

    #[\Override]
    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->resolvedContentNodesLoader = $this->createMock(ResolvedContentNodesLoader::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->provider = new MenuContentNodesProvider(
            $managerRegistry,
            $this->resolvedContentNodesLoader,
            $this->authorizationChecker,
        );

        $this->repository = $this->createMock(ContentNodeRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($this->repository);
    }

    public function testWhenNoContentNodeIds(): void
    {
        $webCatalog = new WebCatalog();
        $contentNode = new ContentNodeStub(10, $webCatalog);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', $webCatalog)
            ->willReturn(true);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->repository
            ->expects(self::once())
            ->method('getContentNodePlainTreeQueryBuilder')
            ->with($contentNode, -1)
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('innerJoin')
            ->with('node.contentVariants', 'contentVariant', Expr\Join::WITH, 'contentVariant.default = true')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('select')
            ->with('node.id as node_id', 'contentVariant.id as variant_id')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query
            ->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([]);

        $this->resolvedContentNodesLoader
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getResolvedContentNode($contentNode));
    }

    public function testWhenNoResolvedContentNode(): void
    {
        $webCatalog = new WebCatalog();
        $contentNode = new ContentNodeStub(10, $webCatalog);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', $webCatalog)
            ->willReturn(true);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->repository
            ->expects(self::once())
            ->method('getContentNodePlainTreeQueryBuilder')
            ->with($contentNode, -1)
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('innerJoin')
            ->with('node.contentVariants', 'contentVariant', Expr\Join::WITH, 'contentVariant.default = true')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('select')
            ->with('node.id as node_id', 'contentVariant.id as variant_id')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $contentVariantIdsByContentNodeId = [
            ['node_id' => 10, 'variant_id' => 101],
            ['node_id' => 20, 'variant_id' => 201],
        ];
        $query
            ->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($contentVariantIdsByContentNodeId);

        $this->resolvedContentNodesLoader
            ->expects(self::once())
            ->method('loadResolvedContentNodes')
            ->with([10 => 101, 20 => 201])
            ->willReturn([]);

        self::assertNull($this->provider->getResolvedContentNode($contentNode));
    }

    public function testWhenHasResolvedContentNode(): void
    {
        $webCatalog = new WebCatalog();
        $contentNode = new ContentNodeStub(10, $webCatalog);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', $webCatalog)
            ->willReturn(true);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->repository
            ->expects(self::once())
            ->method('getContentNodePlainTreeQueryBuilder')
            ->with($contentNode, -1)
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('innerJoin')
            ->with('node.contentVariants', 'contentVariant', Expr\Join::WITH, 'contentVariant.default = true')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('select')
            ->with('node.id as node_id', 'contentVariant.id as variant_id')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $contentVariantIdsByContentNodeId = [
            ['node_id' => 10, 'variant_id' => 101],
            ['node_id' => 20, 'variant_id' => 201],
        ];
        $query
            ->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($contentVariantIdsByContentNodeId);

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $this->resolvedContentNodesLoader
            ->expects(self::once())
            ->method('loadResolvedContentNodes')
            ->with([10 => 101, 20 => 201])
            ->willReturn([10 => $resolvedContentNode]);

        self::assertSame($resolvedContentNode, $this->provider->getResolvedContentNode($contentNode));
    }

    public function testWhenHasResolvedContentNodeAndTreeDepth(): void
    {
        $webCatalog = new WebCatalog();
        $contentNode = new ContentNodeStub(10, $webCatalog);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', $webCatalog)
            ->willReturn(true);

        $context = ['tree_depth' => 4];
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->repository
            ->expects(self::once())
            ->method('getContentNodePlainTreeQueryBuilder')
            ->with($contentNode, $context['tree_depth'])
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects(self::once())
            ->method('innerJoin')
            ->with('node.contentVariants', 'contentVariant', Expr\Join::WITH, 'contentVariant.default = true')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('select')
            ->with('node.id as node_id', 'contentVariant.id as variant_id')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $contentVariantIdsByContentNodeId = [
            ['node_id' => 10, 'variant_id' => 101],
            ['node_id' => 20, 'variant_id' => 201],
        ];
        $query
            ->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($contentVariantIdsByContentNodeId);

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $this->resolvedContentNodesLoader
            ->expects(self::once())
            ->method('loadResolvedContentNodes')
            ->with([10 => 101, 20 => 201])
            ->willReturn([10 => $resolvedContentNode]);

        self::assertSame($resolvedContentNode, $this->provider->getResolvedContentNode($contentNode, $context));
    }

    public function testWhenNoPermissionToWebCatalog(): void
    {
        $webCatalog = new WebCatalog();
        $contentNode = new ContentNodeStub(10, $webCatalog);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', $webCatalog)
            ->willReturn(false);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->willReturn(false);
        $this->repository
            ->expects(self::never())
            ->method(self::anything());
        $this->resolvedContentNodesLoader
            ->expects(self::never())
            ->method(self::anything());
        self::assertNull($this->provider->getResolvedContentNode($contentNode));
    }
}
