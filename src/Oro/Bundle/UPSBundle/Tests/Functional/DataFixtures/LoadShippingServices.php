<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Symfony\Component\Yaml\Yaml;

class LoadShippingServices extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures\LoadShippingCountries'
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach ($this->getShippingServicesData() as $reference => $data) {
            $entity = new ShippingService();
            $entity
                ->setCode($data['code'])
                ->setDescription($data['description']);

            $country = $manager->getRepository(Country::class)
                ->findOneBy(['iso2Code' => $data['country']]);

            $entity->setCountry($country);

            $manager->persist($entity);

            $this->setReference($reference, $entity);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getShippingServicesData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_services.yml'));
    }
}
