<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Placeholder\CustomerIdPlaceholder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison as SearchComparison;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ProductVisibilitySearchQueryModifierTest extends TestCase
{
    private TokenStorageInterface|MockObject $tokenStorage;

    private PlaceholderProvider|MockObject $placeholderProvider;

    private ProductVisibilitySearchQueryModifier $modifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->placeholderProvider = $this->createMock(PlaceholderProvider::class);

        $this->modifier = new ProductVisibilitySearchQueryModifier(
            $this->tokenStorage,
            $this->placeholderProvider
        );
    }

    public function testModify(): void
    {
        $this->placeholderProvider->expects(self::once())
            ->method('getPlaceholderFieldName')
            ->with(
                Product::class,
                ProductVisibilityIndexer::FIELD_VISIBILITY_ACCOUNT,
                [
                    CustomerIdPlaceholder::NAME => 1,
                ]
            )
            ->willReturn('visibility_customer_1');

        $customer = new Customer();
        ReflectionUtil::setId($customer, 1);

        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $query = new Query();
        $this->modifier->modify($query);

        $hidden = BaseVisibilityResolved::VISIBILITY_HIDDEN;
        $visible = BaseVisibilityResolved::VISIBILITY_VISIBLE;

        $expected = new CompositeExpression(
            CompositeExpression::TYPE_OR,
            [
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('integer.is_visible_by_default', Comparison::EQ, new Value($visible)),
                        new SearchComparison(
                            'integer.visibility_customer_1',
                            SearchComparison::NOT_EXISTS,
                            new Value(null)
                        ),
                    ]
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('integer.is_visible_by_default', Comparison::EQ, new Value($hidden)),
                        new Comparison('integer.visibility_customer_1', Comparison::EQ, new Value($visible)),
                    ]
                ),
            ]
        );

        self::assertEquals($expected, $query->getCriteria()->getWhereExpression());
    }

    public function wrongCustomerUserProvider(): array
    {
        return [
            [null],
            [$this->createMock(UserInterface::class)],
        ];
    }

    /**
     * @dataProvider wrongCustomerUserProvider
     */
    public function testModifyForAnonymous(mixed $customerUser): void
    {
        $this->placeholderProvider->expects(self::never())
            ->method('getPlaceholderFieldName');

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $expected = new Comparison(
            'integer.visibility_anonymous',
            Comparison::EQ,
            new Value(BaseVisibilityResolved::VISIBILITY_VISIBLE)
        );

        $query = new Query();
        $this->modifier->modify($query);

        self::assertEquals($expected, $query->getCriteria()->getWhereExpression());
    }

    public function testModifyWhenExplicitCustomer(): void
    {
        $this->placeholderProvider->expects(self::once())
            ->method('getPlaceholderFieldName')
            ->with(
                Product::class,
                ProductVisibilityIndexer::FIELD_VISIBILITY_ACCOUNT,
                [
                    CustomerIdPlaceholder::NAME => 1,
                ]
            )
            ->willReturn('visibility_customer_1');

        $customer = new Customer();
        ReflectionUtil::setId($customer, 1);

        $this->tokenStorage->expects(self::never())
            ->method('getToken');

        $query = new Query();
        $this->modifier->setCurrentCustomer($customer);
        $this->modifier->modify($query);

        $hidden = BaseVisibilityResolved::VISIBILITY_HIDDEN;
        $visible = BaseVisibilityResolved::VISIBILITY_VISIBLE;

        $expected = new CompositeExpression(
            CompositeExpression::TYPE_OR,
            [
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('integer.is_visible_by_default', Comparison::EQ, new Value($visible)),
                        new SearchComparison(
                            'integer.visibility_customer_1',
                            SearchComparison::NOT_EXISTS,
                            new Value(null)
                        ),
                    ]
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('integer.is_visible_by_default', Comparison::EQ, new Value($hidden)),
                        new Comparison('integer.visibility_customer_1', Comparison::EQ, new Value($visible)),
                    ]
                ),
            ]
        );

        self::assertEquals($expected, $query->getCriteria()->getWhereExpression());
    }
}
