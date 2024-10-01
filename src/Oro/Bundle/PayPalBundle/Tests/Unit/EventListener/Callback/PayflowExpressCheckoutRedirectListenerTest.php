<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentResultMessageProviderInterface;
use Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowExpressCheckoutRedirectListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class PayflowExpressCheckoutRedirectListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PayflowExpressCheckoutRedirectListener */
    protected $listener;

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    protected $session;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentMethodProvider;

    /** @var PaymentResultMessageProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $messageProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->messageProvider = $this->createMock(PaymentResultMessageProviderInterface::class);
        $this->paymentTransaction = new PaymentTransaction();

        $this->listener = new PayflowExpressCheckoutRedirectListener(
            $this->requestStack,
            $this->paymentMethodProvider,
            $this->messageProvider
        );
    }

    public function testOnReturnWithoutErrorInFlashBag()
    {
        $this->paymentTransaction
            ->setSuccessful(false)
            ->setTransactionOptions(['failureUrl' => 'failUrlForExpressCheckout'])
            ->setPaymentMethod('payment_method');

        $this->paymentMethodProvider
            ->expects(static::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $message = 'oro.payment.result.error';
        $this->messageProvider->expects($this->once())->method('getErrorMessage')->willReturn($message);

        /** @var FlashBagInterface|\PHPUnit\Framework\MockObject\MockObject $flashBag */
        $flashBag = $this->createMock(FlashBagInterface::class);

        $flashBag->expects($this->once())
            ->method('has')
            ->with('error')
            ->willReturn(false);

        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', $message);

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->listener->onReturn($event);

        $this->assertEquals('failUrlForExpressCheckout', $event->getResponse()->getTargetUrl());
    }

    public function testOnErrorWithWrongTransaction()
    {
        $this->paymentTransaction
            ->setSuccessful(false)
            ->setTransactionOptions(['failureUrl' => 'failUrlForExpressCheckout'])
            ->setPaymentMethod('payment_method');

        $this->paymentMethodProvider
            ->expects(static::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(false);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $this->listener->onReturn($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testOnReturnWithErrorInFlashBag()
    {
        $this->paymentTransaction
            ->setSuccessful(false)
            ->setTransactionOptions(['failureUrl' => 'failUrlForExpressCheckout'])
            ->setPaymentMethod('payment_method');

        $this->paymentMethodProvider
            ->expects(static::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        /** @var FlashBagInterface|\PHPUnit\Framework\MockObject\MockObject $flashBag */
        $flashBag = $this->createMock(FlashBagInterface::class);

        $flashBag->expects($this->once())
            ->method('has')
            ->with('error')
            ->willReturn(true);

        $flashBag->expects($this->never())
            ->method('add')
            ->with('error', 'oro.payment.result.error');

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->listener->onReturn($event);

        $this->assertEquals('failUrlForExpressCheckout', $event->getResponse()->getTargetUrl());
    }

    public function testOnErrorWithoutTransaction()
    {
        $event = new CallbackErrorEvent();

        $this->listener->onReturn($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testOnReturnError10486()
    {
        $this->paymentTransaction
            ->setSuccessful(false)
            ->setTransactionOptions(['failureUrl' => 'failUrlForExpressCheckout'])
            ->setResponse([
                'RESPMSG' =>
                    'Declined: 10486-This transaction couldn\'t be completed. Please redirect your customer to PayPal.'
            ])
            ->setPaymentMethod('payment_method');

        $this->paymentMethodProvider->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $message = 'oro.payment.result.error';
        $this->messageProvider->expects($this->once())->method('getErrorMessage')->willReturn($message);

        /** @var FlashBagInterface|\PHPUnit\Framework\MockObject\MockObject $flashBag */
        $flashBag = $this->createMock(FlashBagInterface::class);

        $flashBag->expects($this->once())
            ->method('has')
            ->with('error')
            ->willReturn(false);

        $flashBag->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                ['warning', 'oro.paypal.result.funding_decline_error'],
                ['error', $message]
            );

        $this->session->expects($this->any())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->listener->onReturn($event);

        $this->assertEquals('failUrlForExpressCheckout', $event->getResponse()->getTargetUrl());
    }
}
