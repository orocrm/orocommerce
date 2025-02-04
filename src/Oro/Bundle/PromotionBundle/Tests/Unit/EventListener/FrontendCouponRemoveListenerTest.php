<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\EventListener\FrontendCouponRemoveListener;
use Oro\Bundle\PromotionBundle\Handler\FrontendCouponRemoveHandler;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Checkout;
use Oro\Bundle\PromotionBundle\ValidationService\CouponValidatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FrontendCouponRemoveListenerTest extends TestCase
{
    public function testOnCheckoutRequest(): void
    {
        $flashBag = self::createMock(FlashBagInterface::class);
        $flashBag
            ->expects(self::once())
            ->method('has')
            ->with('Coupon not valid')
            ->willReturn(false);
        $flashBag
            ->expects(self::once())
            ->method('add')
            ->with('warning', 'Coupon not valid');

        $session = self::createMock(FlashBagAwareSessionInterface::class);
        $session
            ->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $coupon = (new Coupon())->setCode('sale25');
        $request = new Request();
        $request->setSession($session);

        $appliedCoupon = (new AppliedCoupon())->setSourceCouponId(1);
        $checkout = new Checkout();
        $checkout->addAppliedCoupon($appliedCoupon);

        $validator = self::createMock(CouponValidatorInterface::class);
        $validator
            ->expects(self::once())
            ->method('getViolationMessages')
            ->with($coupon, $checkout)
            ->willReturn(['coupon_not_valid']);

        $entityManager = self::createMock(EntityManager::class);
        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(Coupon::class, 1)
            ->willReturn($coupon);

        $managerRegistry = self::createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($entityManager);

        $translator = self::createMock(TranslatorInterface::class);
        $translator
            ->expects(self::once())
            ->method('trans')
            ->with('coupon_not_valid', ['%coupon_name%' => $coupon->getCode()])
            ->willReturn('Coupon not valid');

        $frontendCouponRemoveHandler = self::createMock(FrontendCouponRemoveHandler::class);
        $frontendCouponRemoveHandler
            ->expects(self::once())
            ->method('handleRemove')
            ->with($checkout, $appliedCoupon);

        $event = new CheckoutRequestEvent($request, $checkout);
        $listener = new FrontendCouponRemoveListener(
            $managerRegistry,
            $translator,
            $frontendCouponRemoveHandler,
            [$validator]
        );

        $listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithInvalidSource(): void
    {
        $session = self::createMock(FlashBagAwareSessionInterface::class);

        $request = new Request();
        $request->setSession($session);

        $appliedCoupon = new AppliedCoupon();
        $checkout = new Checkout();
        $checkout->addAppliedCoupon($appliedCoupon);

        $entityManager = self::createMock(EntityManager::class);
        $entityManager
            ->expects(self::never())
            ->method('find');

        $managerRegistry = self::createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($entityManager);

        $translator = self::createMock(TranslatorInterface::class);
        $translator
            ->expects(self::never())
            ->method('trans');

        $frontendCouponRemoveHandler = self::createMock(FrontendCouponRemoveHandler::class);
        $frontendCouponRemoveHandler
            ->expects(self::never())
            ->method('handleRemove');

        $event = new CheckoutRequestEvent($request, $checkout);
        $listener = new FrontendCouponRemoveListener(
            $managerRegistry,
            $translator,
            $frontendCouponRemoveHandler,
            []
        );

        $listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithNonExistsSource(): void
    {
        $session = self::createMock(FlashBagAwareSessionInterface::class);

        $request = new Request();
        $request->setSession($session);

        $appliedCoupon = (new AppliedCoupon())->setSourceCouponId(1);
        $checkout = new Checkout();
        $checkout->addAppliedCoupon($appliedCoupon);

        $entityManager = self::createMock(EntityManager::class);
        $entityManager
            ->expects(self::once())
            ->method('find')
            ->willReturn(null);

        $managerRegistry = self::createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($entityManager);

        $translator = self::createMock(TranslatorInterface::class);
        $translator
            ->expects(self::never())
            ->method('trans');

        $frontendCouponRemoveHandler = self::createMock(FrontendCouponRemoveHandler::class);
        $frontendCouponRemoveHandler
            ->expects(self::never())
            ->method('handleRemove');

        $event = new CheckoutRequestEvent($request, $checkout);
        $listener = new FrontendCouponRemoveListener(
            $managerRegistry,
            $translator,
            $frontendCouponRemoveHandler,
            []
        );

        $listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithoutValidationError(): void
    {
        $session = self::createMock(FlashBagAwareSessionInterface::class);

        $coupon = (new Coupon())->setCode('sale25');
        $request = new Request();
        $request->setSession($session);

        $appliedCoupon = (new AppliedCoupon())->setSourceCouponId(1);
        $checkout = new Checkout();
        $checkout->addAppliedCoupon($appliedCoupon);

        $validator = self::createMock(CouponValidatorInterface::class);
        $validator
            ->expects(self::once())
            ->method('getViolationMessages')
            ->willReturn([]);

        $entityManager = self::createMock(EntityManager::class);
        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(Coupon::class, 1)
            ->willReturn($coupon);

        $managerRegistry = self::createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($entityManager);

        $translator = self::createMock(TranslatorInterface::class);
        $translator
            ->expects(self::never())
            ->method('trans');

        $frontendCouponRemoveHandler = self::createMock(FrontendCouponRemoveHandler::class);
        $frontendCouponRemoveHandler
            ->expects(self::never())
            ->method('handleRemove');

        $event = new CheckoutRequestEvent($request, $checkout);
        $listener = new FrontendCouponRemoveListener(
            $managerRegistry,
            $translator,
            $frontendCouponRemoveHandler,
            [$validator]
        );

        $listener->onCheckoutRequest($event);
    }
}
