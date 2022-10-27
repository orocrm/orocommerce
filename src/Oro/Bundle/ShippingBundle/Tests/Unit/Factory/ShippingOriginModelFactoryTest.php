<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingOriginModelFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var ShippingOriginModelFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->factory = new ShippingOriginModelFactory($this->doctrineHelper);
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(array $values, ShippingOrigin $expected)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(function ($classAlias, $id) {
                if (str_contains($classAlias, 'Country')) {
                    return new Country($id);
                }
                if (str_contains($classAlias, 'Region')) {
                    return new Region($id);
                }

                return null;
            });
        $this->assertEquals($expected, $this->factory->create($values));
    }

    public function createProvider(): array
    {
        return [
            'all' => [
                'values' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                    'city' => 'New York',
                    'street' => 'Street 1',
                    'street2' => 'Street 2',
                ],
                'expected' => (new ShippingOrigin())
                        ->setCountry(new Country('US'))
                        ->setRegion(new Region('US-AL'))
                        ->setCity('New York')
                        ->setStreet('Street 1')
                        ->setStreet2('Street 2')
            ],
            'country and region' => [
                'values' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                ],
                'expected' => (new ShippingOrigin())->setCountry(new Country('US'))->setRegion(new Region('US-AL'))
            ],
            'country only' => [
                'values' => [
                    'country' => 'US',
                ],
                'expected' => (new ShippingOrigin())->setCountry(new Country('US'))
            ],
            'without anything' => [
                'values' => [],
                'expected' => new ShippingOrigin()
            ],
        ];
    }
}
