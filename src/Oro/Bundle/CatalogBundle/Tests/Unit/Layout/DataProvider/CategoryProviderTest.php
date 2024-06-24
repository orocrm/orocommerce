<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProviderBCAdapter;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CategoryProviderTest extends \PHPUnit\Framework\TestCase
{
    private RequestProductHandler|\PHPUnit\Framework\MockObject\MockObject $requestProductHandler;
    private CategoryRepository|\PHPUnit\Framework\MockObject\MockObject $categoryRepository;
    private CategoryTreeProvider|\PHPUnit\Framework\MockObject\MockObject $categoryTreeProvider;
    private TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $tokenAccessor;
    private MasterCatalogRootProviderInterface|\PHPUnit\Framework\MockObject\MockObject $masterCatalogProvider;
    private CategoryProviderBCAdapter|\PHPUnit\Framework\MockObject\MockObject $categoryProviderBCAdapter;
    private CategoryProvider $categoryProvider;

    protected function setUp(): void
    {
        $this->requestProductHandler = $this->createMock(RequestProductHandler::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->categoryTreeProvider = $this->createMock(CategoryTreeProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        // Component added back for theme layout BC from version 5.0
        $this->categoryProviderBCAdapter = $this->createMock(CategoryProviderBCAdapter::class);
        $this->masterCatalogProvider = $this->createMock(MasterCatalogRootProviderInterface::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->categoryProvider = new CategoryProvider(
            $this->requestProductHandler,
            $doctrine,
            $this->categoryTreeProvider,
            $this->tokenAccessor,
            $this->masterCatalogProvider,
            $this->categoryProviderBCAdapter,
        );
    }

    private function getCategory(int $id): Category
    {
        $category = new CategoryStub();
        ReflectionUtil::setId($category, $id);

        return $category;
    }

    public function testGetCurrentCategoryUsingMasterCatalogRoot(): void
    {
        $category = new Category();

        $this->requestProductHandler->expects(self::once())
            ->method('getCategoryId')
            ->willReturn(0);

        $this->masterCatalogProvider->expects(self::once())
            ->method('getMasterCatalogRoot')
            ->willReturn($category);

        $result = $this->categoryProvider->getCurrentCategory();
        self::assertSame($category, $result);
    }

    public function testGetCurrentCategoryUsingFind(): void
    {
        $category = new Category();
        $categoryId = 1;

        $this->requestProductHandler->expects(self::once())
            ->method('getCategoryId')
            ->willReturn($categoryId);

        $this->categoryRepository->expects(self::once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $result = $this->categoryProvider->getCurrentCategory();
        self::assertSame($category, $result);
    }

    /**
     * @dataProvider getIncludeSubcategoriesChoiceDataProvider
     */
    public function testGetIncludeSubcategoriesChoice(bool $result): void
    {
        $this->requestProductHandler->expects(self::once())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn($result);

        self::assertSame($result, $this->categoryProvider->getIncludeSubcategoriesChoice());
    }

    public static function getIncludeSubcategoriesChoiceDataProvider(): array
    {
        return [[false], [true]];
    }

    /**
     * @dataProvider getUserDataProvider
     */
    public function testGetCategoryPath(?UserInterface $userFromToken, ?UserInterface $expectedUser): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($userFromToken);
        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $categoryAId = 1;
        $categoryA = $this->getCategory($categoryAId);
        $categoryB = $this->getCategory(2);

        $this->requestProductHandler->expects(self::once())
            ->method('getCategoryId')
            ->willReturn($categoryAId);

        $this->categoryRepository->expects(self::once())
            ->method('find')
            ->with($categoryAId)
            ->willReturn($categoryA);

        $parentCategories = [
            $categoryA,
            $categoryB,
        ];
        $this->categoryTreeProvider->expects(self::once())
            ->method('getParentCategories')
            ->with($expectedUser, $categoryA)
            ->willReturn($parentCategories);

        self::assertSame(
            $parentCategories,
            $this->categoryProvider->getCategoryPath()
        );
    }

    public function getUserDataProvider(): array
    {
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, 1);

        return [
            'null' => [
                'userFromToken' => null,
                'expectedUser' => null,
            ],
            'not customer user' => [
                'userFromToken' => $this->createMock(UserInterface::class),
                'expectedUser' => null,
            ],
            'customer user' => [
                'userFromToken' => $customerUser,
                'expectedUser' => $customerUser,
            ],
        ];
    }
}
