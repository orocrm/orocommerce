<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\TypeHandler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\TypeHandler\EnumTypeHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;

class EnumTypeHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_CLASS = Product::class;

    /** @var FormFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EnumTypeHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactory::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->handler = new EnumTypeHandler($this->formFactory, self::PRODUCT_CLASS, $this->configManager);
    }

    public function testCreateForm()
    {
        $fieldName = 'field1';
        $availability = [
            'red' => false,
            'green' => true,
            'yellow' => false,
            'black' => true,
            '10' => false,
            '10 mm' => true,
            '10mm' => true,
        ];
        $form = $this->createMock(Form::class);

        $this->formFactory->expects($this->once())
            ->method('createNamed')
            ->with($fieldName, EnumSelectType::class, null, $this->callback(function (array $options) use ($fieldName) {
                $disabledValues = ['red', 'yellow', '10'];

                $this->assertEquals([
                    'class' => EnumOption::class,
                    'configs' => ['allowClear' => false],
                    'disabled_values' => $disabledValues,
                    'auto_initialize' => false,
                    'enum_code' => 'test',
                    'multiple' => false
                ], $options);

                return true;
            }))
            ->willReturn($form);
        $fieldConfig = $this->createMock(FieldConfigModel::class);
        $fieldConfig->method('getType')
            ->willReturn('enum');
        $fieldConfig->expects(self::once())
            ->method('toArray')
            ->with('enum')
            ->willReturn(['target_entity' => EnumOption::class, 'enum_code' => 'test']);
        $this->configManager->expects(self::once())
            ->method('getConfigFieldModel')
            ->with(Product::class, $fieldName)
            ->willReturn($fieldConfig);

        $actualForm = $this->handler->createForm($fieldName, $availability);
        $this->assertSame($form, $actualForm);
    }

    public function testGetType()
    {
        $this->assertEquals('enum', $this->handler->getType());
    }
}
