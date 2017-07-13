<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutListener;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

class CheckoutListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DefaultUserProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $defaultUserProvider;

    /** @var TokenAccessorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenAccessor;

    /** @var CheckoutListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->defaultUserProvider = $this->createMock(DefaultUserProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->listener = new CheckoutListener($this->defaultUserProvider, $this->tokenAccessor);
    }

    public function testPostUpdate()
    {
        $checkout = new Checkout();

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with(
                $checkout,
                ['completedData' => [null, $checkout->getCompletedData()]]
            );

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('getUnitOfWork')->willReturn($uow);

        $this->listener->postUpdate($checkout, new LifecycleEventArgs($checkout, $em));
    }

    /**
     * @dataProvider persistDataProvider
     *
     * @param string $token
     * @param Checkout $checkout
     * @param boolean $setOwner
     */
    public function testPrePersist($token, Checkout $checkout, $setOwner)
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $newUser = new User();
        $newUser->setFirstName('first_name');
        if ($setOwner) {
            $this->defaultUserProvider
                ->expects($this->once())
                ->method('getDefaultUser')
                ->with('oro_checkout', 'default_guest_checkout_owner')
                ->willReturn($newUser);

            $this->listener->prePersist($checkout);
            $this->assertSame($newUser, $checkout->getOwner());
        } else {
            $this->listener->prePersist($checkout);
            $this->assertNotSame($newUser, $checkout->getOwner());
        }
    }

    /**
     * @return array
     */
    public function persistDataProvider()
    {
        return [
            'with token and without owner' => [
                'token' => new AnonymousCustomerUserToken(''),
                'checkout' => new Checkout(),
                'setOwner' => true,
            ],
            'without token and without owner' => [
                'token' => null,
                'checkout' => new Checkout(),
                'setOwner' => false,
            ],
            'unsupported token and without owner' => [
                'token' => new \stdClass(),
                'checkout' => new Checkout(),
                'setOwner' => false,
            ],
            'with owner' => [
                'token' => new AnonymousCustomerUserToken(''),
                'checkout' => (new Checkout())->setOwner(new User()),
                'setOwner' => false,
            ]
        ];
    }
}
