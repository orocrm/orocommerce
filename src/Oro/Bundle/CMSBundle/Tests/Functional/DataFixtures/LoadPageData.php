<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadPageData extends AbstractFixture implements DependentFixtureInterface
{
    public const PAGE_1 = 'page.1';
    public const PAGE_2 = 'page.2';
    public const PAGE_3 = 'page.3';

    protected static array $page = [
        self::PAGE_1 => [],
        self::PAGE_2 => [],
        self::PAGE_3 => [],
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // remove all built-in pages before tests
        $manager->createQuery('DELETE Oro\Bundle\CMSBundle\Entity\Page')->execute();
        foreach (self::$page as $menuItemReference => $data) {
            /** @var Organization $organization */
            $organization = $this->getReference(LoadOrganization::ORGANIZATION);
            $entity = (new Page())
                ->addTitle((new LocalizedFallbackValue())->setString($menuItemReference))
                ->setContent($menuItemReference)
                ->setOrganization($organization);

            $this->setReference($menuItemReference, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }
}
