<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierCheckerInterface;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Twig\ShippingExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShippingExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ShippingMethodLabelFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodLabelFormatter;

    /** @var ShippingMethodEnabledByIdentifierCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodChecker;

    /** @var UnitValueFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dimensionsUnitValueFormatter;

    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $weightUnitLabelFormatter;

    /** @var UnitValueFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $weightUnitValueFormatter;

    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $lengthUnitLabelFormatter;

    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $freightClassLabelFormatter;

    /** @var ShippingExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->shippingMethodLabelFormatter = $this->createMock(ShippingMethodLabelFormatter::class);
        $this->shippingMethodChecker = $this->createMock(ShippingMethodEnabledByIdentifierCheckerInterface::class);
        $this->dimensionsUnitValueFormatter = $this->createMock(UnitValueFormatterInterface::class);
        $this->weightUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);
        $this->weightUnitValueFormatter = $this->createMock(UnitValueFormatterInterface::class);
        $this->lengthUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);
        $this->freightClassLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        $container = self::getContainerBuilder()
            ->add(EventDispatcherInterface::class, $this->dispatcher)
            ->add(DoctrineHelper::class, $this->doctrineHelper)
            ->add('oro_shipping.formatter.shipping_method_label', $this->shippingMethodLabelFormatter)
            ->add('oro_shipping.checker.shipping_method_enabled', $this->shippingMethodChecker)
            ->add('oro_shipping.formatter.dimensions_unit_value', $this->dimensionsUnitValueFormatter)
            ->add('oro_shipping.formatter.weight_unit_label', $this->weightUnitLabelFormatter)
            ->add('oro_shipping.formatter.weight_unit_value', $this->weightUnitValueFormatter)
            ->add('oro_shipping.formatter.length_unit_label', $this->lengthUnitLabelFormatter)
            ->add('oro_shipping.formatter.freight_class_label', $this->freightClassLabelFormatter)
            ->getContainer($this);

        $this->extension = new ShippingExtension($container);
    }

    public function testFormatShippingMethodLabel(): void
    {
        $shippingMethodName = 'method';
        $shippingMethodLabel = 'label';

        $this->shippingMethodLabelFormatter->expects(self::once())
            ->method('formatShippingMethodLabel')
            ->with($shippingMethodName)
            ->willReturn($shippingMethodLabel);

        self::assertEquals(
            $shippingMethodLabel,
            self::callTwigFunction(
                $this->extension,
                'get_shipping_method_label',
                [$shippingMethodName]
            )
        );
    }

    public function testFormatShippingMethodLabelWithOrganization(): void
    {
        $organization = $this->createMock(Organization::class);
        $shippingMethodName = 'method';
        $shippingMethodLabel = 'label';

        $this->shippingMethodLabelFormatter->expects(self::once())
            ->method('formatShippingMethodLabel')
            ->with($shippingMethodName, self::identicalTo($organization))
            ->willReturn($shippingMethodLabel);

        self::assertEquals(
            $shippingMethodLabel,
            self::callTwigFunction(
                $this->extension,
                'get_shipping_method_label',
                [$shippingMethodName, $organization]
            )
        );
    }

    public function testFormatShippingMethodLabelWithOrganizationId(): void
    {
        $organizationId = 123;
        $organization = $this->createMock(Organization::class);
        $shippingMethodName = 'method';
        $shippingMethodLabel = 'label';

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(Organization::class, $organizationId)
            ->willReturn($organization);

        $this->shippingMethodLabelFormatter->expects(self::once())
            ->method('formatShippingMethodLabel')
            ->with($shippingMethodName, self::identicalTo($organization))
            ->willReturn($shippingMethodLabel);

        self::assertEquals(
            $shippingMethodLabel,
            self::callTwigFunction(
                $this->extension,
                'get_shipping_method_label',
                [$shippingMethodName, $organizationId]
            )
        );
    }

    public function testFormatShippingMethodTypeLabelWhenNoShippingMethod(): void
    {
        $shippingMethod = null;
        $shippingMethodType  = null;
        $shippingMethodTypeLabel = 'label';

        $this->shippingMethodLabelFormatter->expects(self::once())
            ->method('formatShippingMethodTypeLabel')
            ->with($shippingMethod, $shippingMethodType, self::isNull())
            ->willReturn($shippingMethodTypeLabel);

        self::assertEquals(
            $shippingMethodTypeLabel,
            self::callTwigFunction(
                $this->extension,
                'get_shipping_method_type_label',
                [$shippingMethod, $shippingMethodType]
            )
        );
    }

    public function testFormatShippingMethodTypeLabelWithOrganization(): void
    {
        $organization = $this->createMock(Organization::class);
        $shippingMethod = 'method';
        $shippingMethodType  = 'type';
        $shippingMethodTypeLabel = 'label';

        $this->shippingMethodLabelFormatter->expects(self::once())
            ->method('formatShippingMethodTypeLabel')
            ->with($shippingMethod, $shippingMethodType, self::identicalTo($organization))
            ->willReturn($shippingMethodTypeLabel);

        self::assertEquals(
            $shippingMethodTypeLabel,
            self::callTwigFunction(
                $this->extension,
                'get_shipping_method_type_label',
                [$shippingMethod, $shippingMethodType, $organization]
            )
        );
    }

    public function testFormatShippingMethodTypeLabelWithOrganizationId(): void
    {
        $organizationId = 123;
        $organization = $this->createMock(Organization::class);
        $shippingMethod = 'method';
        $shippingMethodType  = 'type';
        $shippingMethodTypeLabel = 'label';

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(Organization::class, $organizationId)
            ->willReturn($organization);

        $this->shippingMethodLabelFormatter->expects(self::once())
            ->method('formatShippingMethodTypeLabel')
            ->with($shippingMethod, $shippingMethodType, self::identicalTo($organization))
            ->willReturn($shippingMethodTypeLabel);

        self::assertEquals(
            $shippingMethodTypeLabel,
            self::callTwigFunction(
                $this->extension,
                'get_shipping_method_type_label',
                [$shippingMethod, $shippingMethodType, $organizationId]
            )
        );
    }

    public function testFormatShippingMethodWithTypeLabelWhenNoShippingMethod(): void
    {
        $shippingMethod = null;
        $shippingMethodType  = null;
        $shippingMethodWithTypeLabel = 'label';

        $this->shippingMethodLabelFormatter->expects(self::once())
            ->method('formatShippingMethodWithTypeLabel')
            ->with($shippingMethod, $shippingMethodType, self::isNull())
            ->willReturn($shippingMethodWithTypeLabel);

        self::assertEquals(
            $shippingMethodWithTypeLabel,
            self::callTwigFunction(
                $this->extension,
                'oro_shipping_method_with_type_label',
                [$shippingMethod, $shippingMethodType]
            )
        );
    }

    public function testFormatShippingMethodWithTypeLabelWithOrganization(): void
    {
        $organization = $this->createMock(Organization::class);
        $shippingMethod = 'method';
        $shippingMethodType  = 'type';
        $shippingMethodWithTypeLabel = 'label';

        $this->shippingMethodLabelFormatter->expects(self::once())
            ->method('formatShippingMethodWithTypeLabel')
            ->with($shippingMethod, $shippingMethodType, self::identicalTo($organization))
            ->willReturn($shippingMethodWithTypeLabel);

        self::assertEquals(
            $shippingMethodWithTypeLabel,
            self::callTwigFunction(
                $this->extension,
                'oro_shipping_method_with_type_label',
                [$shippingMethod, $shippingMethodType, $organization]
            )
        );
    }

    public function testFormatShippingMethodWithTypeLabelWithOrganizationId(): void
    {
        $organizationId = 123;
        $organization = $this->createMock(Organization::class);
        $shippingMethod = 'method';
        $shippingMethodType  = 'type';
        $shippingMethodWithTypeLabel = 'label';

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(Organization::class, $organizationId)
            ->willReturn($organization);

        $this->shippingMethodLabelFormatter->expects(self::once())
            ->method('formatShippingMethodWithTypeLabel')
            ->with($shippingMethod, $shippingMethodType, self::identicalTo($organization))
            ->willReturn($shippingMethodWithTypeLabel);

        self::assertEquals(
            $shippingMethodWithTypeLabel,
            self::callTwigFunction(
                $this->extension,
                'oro_shipping_method_with_type_label',
                [$shippingMethod, $shippingMethodType, $organizationId]
            )
        );
    }

    public function testGetShippingMethodConfigRenderDataDefault(): void
    {
        $shippingMethod = 'method';

        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ShippingMethodConfigDataEvent::class), ShippingMethodConfigDataEvent::NAME)
            ->willReturnCallback(function (ShippingMethodConfigDataEvent $event) use ($shippingMethod) {
                self::assertEquals($shippingMethod, $event->getMethodIdentifier());
                $event->setTemplate('@OroShipping/ShippingMethodsConfigsRule/shippingMethodWithOptions.html.twig');

                return $event;
            });

        self::assertEquals(
            '@OroShipping/ShippingMethodsConfigsRule/shippingMethodWithOptions.html.twig',
            self::callTwigFunction($this->extension, 'oro_shipping_method_config_template', [$shippingMethod])
        );

        //test cache
        self::assertEquals(
            '@OroShipping/ShippingMethodsConfigsRule/shippingMethodWithOptions.html.twig',
            self::callTwigFunction($this->extension, 'oro_shipping_method_config_template', [$shippingMethod])
        );
    }

    public function testGetShippingMethodConfigRenderData(): void
    {
        $shippingMethod = 'method';
        $template = '@FooBar/template.html.twig';

        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ShippingMethodConfigDataEvent::class), ShippingMethodConfigDataEvent::NAME)
            ->willReturnCallback(function (ShippingMethodConfigDataEvent $event) use ($shippingMethod, $template) {
                self::assertEquals($shippingMethod, $event->getMethodIdentifier());
                $event->setTemplate($template);

                return $event;
            });

        self::assertEquals(
            $template,
            self::callTwigFunction($this->extension, 'oro_shipping_method_config_template', [$shippingMethod])
        );
    }

    public function testIsShippingMethodEnabled(): void
    {
        $methodIdentifier = 'method_1';

        $this->shippingMethodChecker->expects(self::once())
            ->method('isEnabled')
            ->with($methodIdentifier)
            ->willReturn(true);

        self::assertTrue(
            self::callTwigFunction($this->extension, 'oro_shipping_method_enabled', [$methodIdentifier])
        );
    }

    public function testFormatDimensionsUnitValue(): void
    {
        $value = 42;
        $unit = new LengthUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kilograms';

        $this->dimensionsUnitValueFormatter->expects($this->once())
            ->method('format')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_dimensions_unit_format_value', [$value, $unit])
        );
    }

    public function testFormatDimensionsUnitValueShort(): void
    {
        $value = 42;
        $unit = new LengthUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kg';

        $this->dimensionsUnitValueFormatter->expects($this->once())
            ->method('formatShort')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_dimensions_unit_format_value_short', [$value, $unit])
        );
    }

    public function testFormatDimensionsUnitValueCode(): void
    {
        $value = 42;
        $unitCode = 'kg';
        $expectedResult = '42 kg';

        $this->dimensionsUnitValueFormatter->expects($this->once())
            ->method('formatCode')
            ->with($value, $unitCode)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_dimensions_unit_format_code', [$value, $unitCode])
        );
    }

    /**
     * @dataProvider formatLabelProvider
     */
    public function testFormatWeightUnitLabel(string $code, bool $isShort, bool $isPlural, string $expected): void
    {
        $this->weightUnitLabelFormatter->expects(self::once())
            ->method('format')
            ->with($code, $isShort, $isPlural)
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_weight_unit_format_label', [$code, $isShort, $isPlural])
        );
    }

    public function testFormatWeightUnitValue(): void
    {
        $value = 42;
        $unit = new WeightUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kilograms';

        $this->weightUnitValueFormatter->expects($this->once())
            ->method('format')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_weight_unit_format_value', [$value, $unit])
        );
    }

    public function testFormatWeightUnitValueShort(): void
    {
        $value = 42;
        $unit = new WeightUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kg';

        $this->weightUnitValueFormatter->expects($this->once())
            ->method('formatShort')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_weight_unit_format_value_short', [$value, $unit])
        );
    }

    public function testFormatWeightUnitValueCode(): void
    {
        $value = 42;
        $unitCode = 'kg';
        $expectedResult = '42 kg';

        $this->weightUnitValueFormatter->expects($this->once())
            ->method('formatCode')
            ->with($value, $unitCode)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_weight_unit_format_code', [$value, $unitCode])
        );
    }

    /**
     * @dataProvider formatLabelProvider
     */
    public function testFormatLengthUnitLabel(string $code, bool $isShort, bool $isPlural, string $expected): void
    {
        $this->lengthUnitLabelFormatter->expects(self::once())
            ->method('format')
            ->with($code, $isShort, $isPlural)
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_length_unit_format_label', [$code, $isShort, $isPlural])
        );
    }

    /**
     * @dataProvider formatLabelProvider
     */
    public function testFormatFreightClassLabel(string $code, bool $isShort, bool $isPlural, string $expected): void
    {
        $this->freightClassLabelFormatter->expects(self::once())
            ->method('format')
            ->with($code, $isShort, $isPlural)
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_freight_class_format_label', [$code, $isShort, $isPlural])
        );
    }

    public function formatLabelProvider(): array
    {
        return [
            'format full single' => [
                'code' => 'test_format',
                'isShort' => false,
                'isPlural' => false,
                'expected' => 'formatted_full_single',
            ],
            'format short plural' => [
                'code' => 'test_format',
                'isShort' => true,
                'isPlural'=> true,
                'expected' => 'formatted_short_plural',
            ],
        ];
    }
}
