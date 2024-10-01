<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\AbstractLoadCustomerUserFixture;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LoadUserData extends AbstractLoadCustomerUserFixture
{
    public const USER1 = 'rfp-user1';
    public const USER2 = 'rfp-user2';

    public const ROLE1 = 'rfp-role1';
    public const ROLE2 = 'rfp-role2';
    public const ROLE3 = 'rfp-role3';
    public const ROLE4 = 'rfp-role4';

    public const PARENT_ACCOUNT = 'rfp-parent-customer';
    public const ACCOUNT1 = 'rfp-customer1';
    public const ACCOUNT2 = 'rfp-customer2';

    public const ACCOUNT1_USER1 = 'rfp-customer1-user1@example.com';
    public const ACCOUNT1_USER2 = 'rfp-customer1-user2@example.com';
    public const ACCOUNT1_USER3 = 'rfp-customer1-user3@example.com';
    public const ACCOUNT2_USER1 = 'rfp-customer2-user1@example.com';
    public const ACCOUNT2_USER2 = 'rfp-customer2-user2@example.com';
    public const PARENT_ACCOUNT_USER1 = 'rfp-parent-customer-user1@example.com';
    public const PARENT_ACCOUNT_USER2 = 'rfp-parent-customer-user2@example.com';

    private array $roles = [
        self::ROLE1 => [
            [
                'class' => Request::class,
                'acls'  => ['VIEW_BASIC', 'CREATE_BASIC', 'EDIT_BASIC'],
            ],
            [
                'class' => CustomerUser::class,
                'acls'  => [],
            ],
            [
                'oid'  => 'workflow:(root)',
                'acls' => ['VIEW_WORKFLOW_SYSTEM', 'PERFORM_TRANSITIONS_SYSTEM'],
            ],
        ],
        self::ROLE2 => [
            [
                'class' => Request::class,
                'acls'  => ['VIEW_LOCAL'],
            ],
            [
                'class' => CustomerUser::class,
                'acls'  => ['VIEW_LOCAL'],
            ],
        ],
        self::ROLE3 => [
            [
                'class' => Request::class,
                'acls'  => ['VIEW_BASIC'],
            ],
            [
                'class' => CustomerUser::class,
                'acls'  => ['VIEW_LOCAL'],
            ],
        ],
        self::ROLE4 => [
            [
                'class' => Request::class,
                'acls'  => ['VIEW_DEEP', 'CREATE_DEEP', 'EDIT_DEEP'],
            ],
            [
                'class' => CustomerUser::class,
                'acls'  => ['VIEW_DEEP'],
            ],
        ]
    ];

    private array $customers = [
        [
            'name' => self::PARENT_ACCOUNT,
        ],
        [
            'name' => self::ACCOUNT2,
            'parent' => self::PARENT_ACCOUNT
        ],
        [
            'name' => self::ACCOUNT1,
            'parent' => self::PARENT_ACCOUNT
        ],
    ];

    private array $customerUsers = [
        [
            'email'     => self::ACCOUNT1_USER1,
            'firstname' => 'User1FN',
            'lastname'  => 'User1LN',
            'password'  => self::ACCOUNT1_USER1,
            'customer'   => self::ACCOUNT1,
            'role'      => self::ROLE1,
        ],
        [
            'email'     => self::ACCOUNT1_USER2,
            'firstname' => 'User2FN',
            'lastname'  => 'User2LN',
            'password'  => self::ACCOUNT1_USER2,
            'customer'   => self::ACCOUNT1,
            'role'      => self::ROLE2,
        ],
        [
            'email'     => self::ACCOUNT1_USER3,
            'firstname' => 'User3FN',
            'lastname'  => 'User3LN',
            'password'  => self::ACCOUNT1_USER3,
            'customer'   => self::ACCOUNT1,
            'role'      => self::ROLE3,
        ],
        [
            'email'     => self::ACCOUNT2_USER1,
            'firstname' => 'User21FN',
            'lastname'  => 'User21LN',
            'password'  => self::ACCOUNT2_USER1,
            'customer'   => self::ACCOUNT2,
            'role'      => self::ROLE1,
        ],
        [
            'email'     => self::ACCOUNT2_USER2,
            'firstname' => 'User22FN',
            'lastname'  => 'User22LN',
            'password'  => self::ACCOUNT2_USER2,
            'customer'   => self::ACCOUNT2,
            'role'      => self::ROLE4,
        ],
        [
            'email'     => self::PARENT_ACCOUNT_USER1,
            'firstname' => 'PAUser1FN',
            'lastname'  => 'PAUser1LN',
            'password'  => self::PARENT_ACCOUNT_USER1,
            'customer'   => self::PARENT_ACCOUNT,
            'role'      => self::ROLE4,
        ],
        [
            'email'     => self::PARENT_ACCOUNT_USER2,
            'firstname' => 'PAUser2FN',
            'lastname'  => 'PAUser2LN',
            'password'  => self::PARENT_ACCOUNT_USER2,
            'customer'   => self::PARENT_ACCOUNT,
            'role'      => self::ROLE2,
        ],
    ];

    private array $users = [
        [
            'email'     => 'rfp-user1@example.com',
            'username'  => self::USER1,
            'password'  => self::USER1,
            'firstname' => 'RFPUser1FN',
            'lastname'  => 'RFPUser1LN',
        ],
        [
            'email'     => 'rfp-user2@example.com',
            'username'  => self::USER2,
            'password'  => self::USER2,
            'firstname' => 'RFPUser2FN',
            'lastname'  => 'RFPUser2LN',
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadUsers();
        parent::load($manager);
    }

    private function loadUsers(): void
    {
        /* @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');
        $defaultUser = $this->getUser();
        $roles = $defaultUser->getUserRoles();
        foreach ($this->users as $item) {
            /* @var User $user */
            $user = $userManager->createUser();
            $user
                ->setEmail($item['email'])
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setBusinessUnits($defaultUser->getBusinessUnits())
                ->setOwner($defaultUser->getOwner())
                ->setOrganization($defaultUser->getOrganization())
                ->addUserRole($roles[0])
                ->setUsername($item['username'])
                ->setPlainPassword($item['password'])
                ->setEnabled(true);
            $userManager->updateUser($user);
            $this->setReference($user->getUserIdentifier(), $user);
        }
    }

    #[\Override]
    protected function getCustomers(): array
    {
        return $this->customers;
    }

    #[\Override]
    protected function getRoles(): array
    {
        return $this->roles;
    }

    #[\Override]
    protected function getCustomerUsers(): array
    {
        return $this->customerUsers;
    }
}
