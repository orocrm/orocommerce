<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\Page;
use Oro\Bundle\DraftBundle\Entity\DraftProject;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class PageTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $this->assertPropertyAccessors(new Page(), [
            ['id', 1],
            ['content', 'test_content'],
            ['organization', new Organization()],
            ['draftUuid', UUIDGenerator::v4()],
            ['draftProject', new DraftProject()],
            ['draftSource', new Page()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['slugPrototypesWithRedirect', new SlugPrototypesWithRedirect(new ArrayCollection(), false), false],
        ]);

        $this->assertPropertyCollections(new Page(), [
            ['slugPrototypes', new LocalizedFallbackValue()],
            ['slugs', new Slug()],
        ]);
    }

    public function testToString(): void
    {
        $value = 'test';

        $page = new Page();
        $page->addTitle((new LocalizedFallbackValue())->setString($value));

        $this->assertEquals($value, (string)$page);
    }

    public function testClearSlugs(): void
    {
        $page = new Page();
        $localizeValue = (new LocalizedFallbackValue())->setString('Test');

        $page->setSlugPrototypesWithRedirect(new SlugPrototypesWithRedirect(new ArrayCollection([$localizeValue])));
        $page->addSlug(new Slug());
        $page->addSlugPrototype($localizeValue);

        $page->clearSlugs();

        self::assertTrue($page->getSlugPrototypes()->isEmpty());
        self::assertTrue($page->getSlugs()->isEmpty());
        self::assertTrue($page->getSlugPrototypesWithRedirect()->getSlugPrototypes()->isEmpty());
    }
}
