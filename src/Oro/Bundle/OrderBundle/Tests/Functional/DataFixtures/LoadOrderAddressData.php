<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

class LoadOrderAddressData extends AbstractFixture implements DependentFixtureInterface
{
    const ORDER_ADDRESS_1 = 'order_address.office';
    const ORDER_ADDRESS_2 = 'order_address.warehouse';
    const ORDER_ADDRESS_3 = 'order_address.order2.billing';
    const ORDER_ADDRESS_4 = 'order_address.order2.shipping';

    /**
     * @var array
     */
    protected $addresses = [
        self::ORDER_ADDRESS_1 => [
            'order' => LoadOrders::ORDER_1,
            'type' => 'billing',
            'country' => 'US',
            'city' => 'Rochester',
            'region' => 'US-NY',
            'street' => '1215 Caldwell Road',
            'postalCode' => '14608',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ],
        self::ORDER_ADDRESS_2 => [
            'order' => LoadOrders::ORDER_1,
            'type' => 'shipping',
            'country' => 'US',
            'city' => 'Romney',
            'region' => 'US-IN',
            'street' => '2413 Capitol Avenue',
            'postalCode' => '47981',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ],
        self::ORDER_ADDRESS_3 => [
            'order' => LoadOrders::ORDER_2,
            'type' => 'billing',
            'country' => 'US',
            'city' => 'Rochester',
            'region' => 'US-NY',
            'street' => '1215 Caldwell Road',
            'postalCode' => '14608',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ],
        self::ORDER_ADDRESS_4 => [
            'order' => LoadOrders::ORDER_2,
            'type' => 'shipping',
            'country' => 'US',
            'city' => 'Romney',
            'region' => 'US-IN',
            'street' => '2413 Capitol Avenue',
            'postalCode' => '47981',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]
    ];

    #[\Override]
    public function getDependencies()
    {
        return [
            'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach ($this->addresses as $name => $address) {
            /** @var Order $order */
            $order = $this->getReference($address['order']);
            $orderAddress = $this->createOrderAddress($manager, $name, $address);

            if ($address['type'] === 'billing') {
                $order->setBillingAddress($orderAddress);
            } else {
                $order->setShippingAddress($orderAddress);
            }
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param array $address
     * @return OrderAddress
     */
    protected function createOrderAddress(ObjectManager $manager, $name, array $address)
    {
        /** @var Country $country */
        $country = $manager->getReference(Country::class, $address['country']);
        if (!$country) {
            throw new \RuntimeException('Can\'t find country with ISO ' . $address['country']);
        }

        /** @var Region $region */
        $region = $manager->getReference(Region::class, $address['region']);
        if (!$region) {
            throw new \RuntimeException(
                sprintf('Can\'t find region with code %s', $address['region'])
            );
        }

        $orderAddress = new OrderAddress();
        $orderAddress
            ->setCountry($country)
            ->setCity($address['city'])
            ->setRegion($region)
            ->setStreet($address['street'])
            ->setPostalCode($address['postalCode'])
            ->setFirstName($address['firstName'])
            ->setLastName($address['lastName']);

        $manager->persist($orderAddress);
        $this->addReference($name, $orderAddress);

        return $orderAddress;
    }
}
