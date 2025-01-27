<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider\MethodsConfigsRule\Context\Basic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\Basic\BasicMethodsConfigsRulesByContextProvider;
use Oro\Bundle\PaymentBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class BasicMethodsConfigsRulesByContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MethodsConfigsRulesFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filtrationService;

    /** @var PaymentMethodsConfigsRuleRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var BasicMethodsConfigsRulesByContextProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(MethodsConfigsRulesFiltrationServiceInterface::class);
        $this->repository = $this->createMock(PaymentMethodsConfigsRuleRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(PaymentMethodsConfigsRule::class)
            ->willReturn($this->repository);

        $this->provider = new BasicMethodsConfigsRulesByContextProvider(
            $this->filtrationService,
            $doctrine
        );
    }

    public function testGetPaymentMethodsConfigsRulesWhenNoCurrency(): void
    {
        $this->repository->expects(self::never())
            ->method(self::anything());

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::any())
            ->method('getCurrency')
            ->willReturn(null);

        $this->filtrationService->expects(self::never())
            ->method('getFilteredPaymentMethodsConfigsRules');

        self::assertSame([], $this->provider->getPaymentMethodsConfigsRules($context));
    }

    public function testGetPaymentMethodsConfigsRulesWithPaymentAddress(): void
    {
        $currency = 'USD';
        $address = $this->createMock(AddressInterface::class);
        $website = $this->createMock(Website::class);
        $rulesFromDb = [$this->createMock(PaymentMethodsConfigsRule::class)];

        $this->repository->expects(self::once())
            ->method('getByDestinationAndCurrencyAndWebsite')
            ->with($address, $currency, $website)
            ->willReturn($rulesFromDb);

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::any())
            ->method('getCurrency')
            ->willReturn($currency);
        $context->expects(self::any())
            ->method('getBillingAddress')
            ->willReturn($address);
        $context->expects(self::any())
            ->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [
            $this->createMock(PaymentMethodsConfigsRule::class),
            $this->createMock(PaymentMethodsConfigsRule::class),
        ];

        $this->filtrationService->expects(self::once())
            ->method('getFilteredPaymentMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        self::assertSame(
            $expectedRules,
            $this->provider->getPaymentMethodsConfigsRules($context)
        );
    }

    public function testGetPaymentMethodsConfigsRulesWithoutPaymentAddress(): void
    {
        $currency = 'USD';
        $website = $this->createMock(Website::class);
        $rulesFromDb = [$this->createMock(PaymentMethodsConfigsRule::class)];

        $this->repository->expects(self::once())
            ->method('getByCurrencyAndWebsiteWithoutDestination')
            ->with($currency, $website)
            ->willReturn($rulesFromDb);

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::any())
            ->method('getCurrency')
            ->willReturn($currency);
        $context->expects(self::any())
            ->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [$this->createMock(PaymentMethodsConfigsRule::class)];

        $this->filtrationService->expects(self::once())
            ->method('getFilteredPaymentMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        self::assertSame(
            $expectedRules,
            $this->provider->getPaymentMethodsConfigsRules($context)
        );
    }
}
