<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadCustomerGroupProductVisibilityScopes extends AbstractFixture implements
    InitialFixtureInterface,
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies()
    {
        return [LoadGroups::class];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $visibilityProvider = $this->container->get('oro_visibility.provider.visibility_scope_provider');
        $websiteManager = $this->container->get('oro_website.manager');
        $website = $websiteManager->getDefaultWebsite();

        $scope1 = $visibilityProvider->getCustomerGroupProductVisibilityScope(
            $this->getReference('customer_group.group1'),
            $website
        );
        $this->addReference('scope_1', $scope1);

        $scope2 = $visibilityProvider->getCustomerGroupProductVisibilityScope(
            $this->getReference('customer_group.group2'),
            $website
        );
        $this->addReference('scope_2', $scope2);

        $scope2 = $visibilityProvider->getCustomerGroupProductVisibilityScope(
            $this->getReference('customer_group.group3'),
            $website
        );
        $this->addReference('scope_3', $scope2);
    }
}
