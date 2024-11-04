<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
* SlugAware trait
*
*/
trait SlugAwareTrait
{
    /**
     * @var Collection<int, Slug>
     */
    #[ORM\ManyToMany(targetEntity: Slug::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Collection $slugs = null;

    /**
     * @return Collection|Slug[]
     */
    public function getSlugs()
    {
        return $this->slugs;
    }

    /**
     * @param Slug $slug
     * @return $this
     */
    public function addSlug(Slug $slug)
    {
        if (!$this->hasSlug($slug)) {
            $this->slugs->add($slug);
        }
        return $this;
    }

    /**
     * @param Slug $slug
     * @return $this
     */
    public function removeSlug(Slug $slug)
    {
        if ($this->hasSlug($slug)) {
            $this->slugs->removeElement($slug);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function resetSlugs()
    {
        $this->slugs->clear();

        return $this;
    }

    /**
     * @param Slug $slug
     * @return bool
     */
    public function hasSlug(Slug $slug)
    {
        return $this->slugs->contains($slug);
    }

    public function getBaseSlug()
    {
        return $this->getSlugByLocalization(null);
    }

    public function getSlugByLocalization(Localization $localization = null)
    {
        foreach ($this->getSlugs() as $slug) {
            if (null === $localization) {
                if (null === $slug->getLocalization()) {
                    return $slug;
                }
            } elseif ($slug->getLocalization() && $slug->getLocalization()->getId() === $localization->getId()) {
                return $slug;
            }
        }

        return null;
    }
}
