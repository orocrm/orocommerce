<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\FormViewListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Exception\UnexpectedTypeException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class FormViewListenerTest extends TestCase
{
    private DoctrineHelper|MockObject $doctrineHelper;
    private AuthorizationCheckerInterface|MockObject $authorizationChecker;
    private FieldAclHelper|MockObject $fieldAclHelper;
    private Environment|MockObject $env;
    private FormViewListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->any())
            ->method('trans')
            ->willReturnCallback(fn ($id) => $id . '.trans');

        $this->env = $this->createMock(Environment::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->fieldAclHelper = $this->createMock(FieldAclHelper::class);
        $this->fieldAclHelper
            ->expects($this->any())
            ->method('isFieldAvailable')
            ->willReturn(true);

        $this->fieldAclHelper
            ->expects($this->any())
            ->method('isFieldViewGranted')
            ->willReturn(true);

        $this->listener = new FormViewListener(
            $translator,
            $this->doctrineHelper,
            $this->authorizationChecker,
            $this->fieldAclHelper
        );
    }

    public function testOnProductEdit()
    {
        $formView = new FormView();

        $this->env->expects($this->once())
            ->method('render')
            ->with('@OroCatalog/Product/category_update.html.twig', ['form' => $formView])
            ->willReturn('');

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_catalog_category_view')
            ->willReturn(true);

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new Product(), $formView);
        $this->listener->onProductEdit($event);
    }

    public function testOnProductEditWhenCatalogViewDisabledByAcl()
    {
        $formView = new FormView();

        $this->env->expects($this->never())
            ->method('render');

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_catalog_category_view')
            ->willReturn(false);

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new Product(), $formView);
        $this->listener->onProductEdit($event);
    }

    public function testOnProductView()
    {
        $repository = $this->createMock(CategoryRepository::class);

        $product = new Product();
        $category = new Category();

        $repository->expects($this->once())
            ->method('findOneByProduct')
            ->with($product)
            ->willReturn($category);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Category::class)
            ->willReturn($repository);

        $this->env->expects($this->once())
            ->method('render')
            ->with('@OroCatalog/Product/category_view.html.twig', ['entity' => $category])
            ->willReturn('');

        $scrollData = $this->getPreparedScrollData();

        $event = new BeforeListRenderEvent($this->env, $scrollData, new Product());

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_catalog_category_view')
            ->willReturn(true);

        $this->listener->onProductView($event);
        $this->assertScrollData($scrollData);
    }

    public function testOnProductViewWhenCatalogViewDisabledByAcl()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->env->expects($this->never())
            ->method('render');

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new Product());

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_catalog_category_view')
            ->willReturn(false);

        $this->listener->onProductView($event);
    }

    public function testOnProductViewWithoutCategory()
    {
        $repository = $this->createMock(CategoryRepository::class);

        $product = new Product();

        $repository->expects($this->once())
            ->method('findOneByProduct')
            ->with($product)
            ->willReturn(null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Category::class)
            ->willReturn($repository);

        $this->env->expects($this->never())
            ->method('render');

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new Product());

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_catalog_category_view')
            ->willReturn(true);

        $this->listener->onProductView($event);
    }

    public function testOnProductViewInvalidEntity()
    {
        $this->expectException(UnexpectedTypeException::class);
        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent($this->env, $scrollData, new \stdClass());

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_catalog_category_view')
            ->willReturn(true);

        $this->listener->onProductView($event);
    }

    private function getPreparedScrollData(): ScrollData
    {
        $data[ScrollData::DATA_BLOCKS]['general'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA] = [
            'productName' => [],
        ];

        return new ScrollData($data);
    }

    private function assertScrollData(ScrollData $scrollData)
    {
        $data = $scrollData->getData();
        $generalBlockData = $data[ScrollData::DATA_BLOCKS]['general'][ScrollData::SUB_BLOCKS]
            [0][ScrollData::DATA];

        $this->assertArrayHasKey('productName', $generalBlockData);
        $this->assertArrayHasKey('category', $generalBlockData);

        reset($generalBlockData);
        $this->assertEquals('category', key($generalBlockData), 'Category not a first element');
    }
}
