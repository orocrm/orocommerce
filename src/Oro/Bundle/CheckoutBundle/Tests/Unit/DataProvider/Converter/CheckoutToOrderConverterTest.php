<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Converter;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CacheBundle\Tests\Unit\Provider\MemoryCacheProviderAwareTestTrait;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class CheckoutToOrderConverterTest extends \PHPUnit\Framework\TestCase
{
    use MemoryCacheProviderAwareTestTrait;

    /**
     * @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutLineItemsManager;

    /**
     * @var MapperInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mapper;

    /**
     * @var EntityPaymentMethodsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentMethodsProvider;

    /**
     * @var CheckoutToOrderConverter
     */
    private $converter;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheProvider;

    protected function setUp()
    {
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->mapper = $this->createMock(MapperInterface::class);
        $this->cacheProvider = $this->createMock(CacheProvider::class);
        $this->paymentMethodsProvider = $this->createMock(EntityPaymentMethodsProvider::class);

        $this->converter = new CheckoutToOrderConverter(
            $this->checkoutLineItemsManager,
            $this->mapper,
            $this->cacheProvider,
            $this->paymentMethodsProvider
        );
    }

    public function testGetOrder(): void
    {
        $checkout = new Checkout();
        $checkout->setPaymentMethod('pm1');
        $order = new Order();

        $cacheKey = md5(serialize($checkout));

        $lineItems = new ArrayCollection([new OrderLineItem()]);

        $this->cacheProvider
            ->expects($this->once())
            ->method('save')
            ->with($cacheKey, $order);

        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('fetch')
            ->with($cacheKey)
            ->willReturnOnConsecutiveCalls(false, $order);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($lineItems);

        $this->mapper->expects($this->once())
            ->method('map')
            ->with($checkout, ['lineItems' => $lineItems])
            ->willReturn($order);

        $this->paymentMethodsProvider->expects($this->exactly(2))
            ->method('storePaymentMethodsToEntity')
            ->with($order, ['pm1']);

        $this->assertSame($order, $this->converter->getOrder($checkout));

        // check local cache
        $this->assertSame($order, $this->converter->getOrder($checkout));
    }

    public function testGetOrderWithMemoryCache(): void
    {
        $checkout = new Checkout();
        $checkout->setPaymentMethod('pm1');
        $order = new Order();

        $lineItems = new ArrayCollection([new OrderLineItem()]);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($lineItems);

        $this->mapper->expects($this->once())
            ->method('map')
            ->with($checkout, ['lineItems' => $lineItems])
            ->willReturn($order);

        $this->paymentMethodsProvider->expects($this->once())
            ->method('storePaymentMethodsToEntity')
            ->with($order, ['pm1']);

        $this->mockMemoryCacheProvider();
        $this->setMemoryCacheProvider($this->converter);

        $this->cacheProvider
            ->expects($this->never())
            ->method($this->anything());

        $this->assertSame($order, $this->converter->getOrder($checkout));
    }

    public function testGetOrderWithMemoryCacheAndCachedData(): void
    {
        $checkout = new Checkout();
        $checkout->setPaymentMethod('pm1');
        $order = new Order();

        $this->checkoutLineItemsManager->expects($this->never())
            ->method($this->anything());

        $this->mapper->expects($this->never())
            ->method($this->anything());

        $this->paymentMethodsProvider->expects($this->once())
            ->method('storePaymentMethodsToEntity')
            ->with($order, ['pm1']);

        $this->mockMemoryCacheProvider($order);
        $this->setMemoryCacheProvider($this->converter);

        $this->cacheProvider->expects($this->never())
            ->method($this->anything());

        $this->assertSame($order, $this->converter->getOrder($checkout));
    }
}
