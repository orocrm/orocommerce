<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Update CPL activation rules for CPLs that contain price lists with schedules but do not have activation rules.
 */
class ActualizeCplActivationRules extends AbstractFixture implements
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function load(ObjectManager $manager)
    {
        $this->container->get('oro_pricing.migrations.actualize_cpl_activation_rules_migration')->migrate();
    }
}
