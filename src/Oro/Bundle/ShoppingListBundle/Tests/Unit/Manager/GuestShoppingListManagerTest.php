<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\CustomerVisitorStub;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class GuestShoppingListManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var GuestShoppingListManager */
    private $guestShoppingListManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->guestShoppingListManager = new GuestShoppingListManager(
            $this->doctrineHelper,
            $this->tokenStorage,
            $this->websiteManager,
            $this->translator,
            $this->configManager
        );
        $this->guestShoppingListManager->setFeatureChecker($this->featureChecker);
        $this->guestShoppingListManager->addFeature('guest_shopping_list');
    }

    public function testGetDefaultUserWithId()
    {
        $user = new User();

        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $this->assertSame($user, $this->guestShoppingListManager->getDefaultUser(1));
    }

    public function testGetDefaultUserWithNull()
    {
        $user = new User();

        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($user);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $this->assertSame($user, $this->guestShoppingListManager->getDefaultUser(null));
    }

    /**
     * @dataProvider availableDataProvider
     */
    public function testIsGuestShoppingListAvailable(string $tokenClass, bool $isFeatureEnabled, bool $expectedResult)
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock($tokenClass));

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('guest_shopping_list')
            ->willReturn($isFeatureEnabled);

        $this->assertEquals($expectedResult, $this->guestShoppingListManager->isGuestShoppingListAvailable());
    }

    /**
     * @dataProvider getGuestShoppingListsDataProvider
     */
    public function testGetShoppingListsForCustomerVisitor(
        Website $currentWebsite,
        CustomerVisitorStub $customerVisitor,
        array $expectedShoppingList
    ) {
        $token = new AnonymousCustomerUserToken($customerVisitor, []);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($currentWebsite);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $em = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->assertEquals(
            $expectedShoppingList,
            $this->guestShoppingListManager->getShoppingListsForCustomerVisitor()
        );
    }

    public function testGetShoppingListForCustomerVisitorInValidToken()
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Token should be instance of Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken.'
        );

        $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
    }

    public function testGetShoppingListForCustomerVisitorEmptyVisitor()
    {
        $token = new AnonymousCustomerUserToken(new CustomerVisitor());
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Current website is empty.');

        $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
    }

    public function testGetShoppingListForCustomerVisitorEmptyWebsite()
    {
        $token = new AnonymousCustomerUserToken(new CustomerVisitor());
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Current website is empty.');

        $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
    }

    public function availableDataProvider(): array
    {
        return [
            'not valid token' => [
                'tokenClass' => TokenInterface::class,
                'isFeatureEnabled' => true,
                'expectedResult' => false,
            ],
            'feature disabled' => [
                'tokenClass' => AnonymousCustomerUserToken::class,
                'isFeatureEnabled' => false,
                'expectedResult' => false,
            ],
            'valid token and enabled feature' => [
                'tokenClass' => AnonymousCustomerUserToken::class,
                'isFeatureEnabled' => true,
                'expectedResult' => true,
            ],
        ];
    }

    public function getGuestShoppingListsDataProvider(): array
    {
        $customerVisitorWithFewShoppingLists = new CustomerVisitorStub();

        $organization = new Organization();

        $website1 = $this->getEntity(Website::class, [
            'id' => 1,
            'organization' => $organization
        ]);
        $website2 = $this->getEntity(Website::class, [
            'id' => 2,
            'organization' => $organization
        ]);

        /** @var ShoppingList $shoppingList1 */
        $shoppingList1 = $this->getEntity(ShoppingList::class, [
            'id' => 25,
            'website' => $website1,
            'organization' => $organization
        ]);
        $expectedShoppingList1 = $this->getEntity(ShoppingList::class, [
            'id' => 25,
            'website' => $website1,
            'organization' => $organization
        ]);
        /** @var ShoppingList $shoppingList2 */
        $shoppingList2 = $this->getEntity(ShoppingList::class, [
            'id' => 31,
            'website' => $website2,
            'organization' => $organization
        ]);
        $expectedShoppingList2 = $this->getEntity(ShoppingList::class, [
            'id' => 31,
            'website' => $website2,
            'organization' => $organization
        ]);

        $customerVisitorWithFewShoppingLists->addShoppingList($shoppingList1);
        $customerVisitorWithFewShoppingLists->addShoppingList($shoppingList2);

        $customerVisitorWithOneShoppingList = new CustomerVisitorStub();
        $customerVisitorWithOneShoppingList->addShoppingList($shoppingList2);

        return [
            'customer visitor has few shopping list' => [
                'currentWebsite' => $website1,
                'customerVisitor' => $customerVisitorWithFewShoppingLists,
                'expectedShoppingList' => [$expectedShoppingList1->setCurrent(true)],
            ],
            'customer visitor has one shopping list' => [
                'currentWebsite' => $website2,
                'customerVisitor' => $customerVisitorWithOneShoppingList,
                'expectedShoppingList' => [$expectedShoppingList2->setCurrent(true)],
            ],
            'customer visitor does not have shopping list' => [
                'currentWebsiteId' => $website1,
                'customerVisitor' => new CustomerVisitorStub(),
                'expectedShoppingList' => [],
            ],
        ];
    }

    /**
     * @dataProvider createGuestShoppingListDataProvider
     */
    public function testCreateAndGetShoppingListForCustomerVisitor(
        Website $currentWebsite,
        CustomerVisitorStub $customerVisitor,
        ShoppingList $expectedShoppingList
    ) {
        $token = new AnonymousCustomerUserToken($customerVisitor);

        $this->websiteManager->expects($this->atLeastOnce())
            ->method('getCurrentWebsite')
            ->willReturn($currentWebsite);

        $this->tokenStorage->expects($this->atLeastOnce())
            ->method('getToken')
            ->willReturn($token);

        $this->translator->expects($this->any())
            ->method('trans')
            ->with('oro.shoppinglist.default.label')
            ->willReturn('Shopping List Label');

        $em = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);
        $actual = $this->guestShoppingListManager->createAndGetShoppingListForCustomerVisitor();

        $this->assertEquals($expectedShoppingList, $actual);
    }

    public function createGuestShoppingListDataProvider(): array
    {
        $customerVisitor = new CustomerVisitorStub();

        $organization = new Organization();
        $website = $this->getEntity(Website::class, [
            'id' => 1,
            'organization' => $organization
        ]);

        /** @var ShoppingList $shoppingList1 */
        $shoppingList1 = $this->getEntity(ShoppingList::class, [
            'id' => 25,
            'website' => $website,
            'organization' => $organization
        ]);
        $expectedShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => 25,
            'website' => $website,
            'organization' => $organization
        ]);
        /** @var ShoppingList $shoppingList2 */
        $shoppingList2 = $this->getEntity(ShoppingList::class, [
            'id' => 31,
            'website' => $website,
            'organization' => $organization
        ]);

        $customerVisitor->addShoppingList($shoppingList1);
        $customerVisitor->addShoppingList($shoppingList2);

        return [
            'shopping lists exist' => [
                'currentWebsite' => $website,
                'customerVisitor' => $customerVisitor,
                'expectedShoppingList' => $expectedShoppingList->setCurrent(true),
            ],
            'shopping list doesn\'t exist' => [
                'currentWebsite' => $website,
                'customerVisitor' => new CustomerVisitorStub(),
                'expectedShoppingList' => $this->getEntity(ShoppingList::class, [
                    'id' => null,
                    'label' => 'Shopping List Label',
                    'website' => $website,
                    'current' => true,
                    'organization' => $organization,
                ]),
            ],
        ];
    }
}
