<?php

namespace Oro\Bundle\CMSBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Doctrine repository for Page entity
 */
class PageRepository extends EntityRepository
{
    public function findOneByTitle(string $title, Localization $localization = null, bool $partial = true): ?Page
    {
        $qb = $this->createQueryBuilder('page');

        if ($localization === null) {
            $joinExpr = $qb->expr()->isNull('title.localization');
        } else {
            $joinExpr = $qb->expr()->eq('title.localization', ':localization');
            $qb->setParameter('localization', $localization);
        }

        if ($partial) {
            $qb->select('partial page.{id}');
        }

        return $qb
            ->innerJoin('page.titles', 'title', Join::WITH, $joinExpr)
            ->andWhere('title.string = :title')
            ->setParameter('title', $title)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNonExistentPageIds(array $pageIds): array
    {
        if (empty($pageIds)) {
            return [];
        }

        $pageIds = array_unique($pageIds);

        $qb = $this->createQueryBuilder('page');
        $qb
            ->select('page.id')
            ->where($qb->expr()->in('page.id', ':pageIds'));

        $qb->setParameter('pageIds', $pageIds);

        $result = $qb->getQuery()->getArrayResult();

        $existedPageIds = array_column($result, 'id');

        return array_diff($pageIds, $existedPageIds);
    }
}
