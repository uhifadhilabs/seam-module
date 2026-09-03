<?php

declare(strict_types=1);

/*
 * This file is part of the UhifadhiLabs Seam Module.
 *
 * (c) Ezekiel Mjema <https://github.com/eemjema>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Uhifadhi\Seam\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Uhifadhi\Seam\Entity\AreaInterface;
use Uhifadhi\Seam\Entity\AreaModule;

/**
 * Per-area install state, read in the area's own order.
 *
 * EVERY READING IS A QUERY. Nothing here is memoised, and that is the design,
 * not an omission: the promise the platform makes to an area manager is that
 * switching a module off takes its contributions off the page the same day, and
 * a cache in front of these two methods is precisely how that promise stops
 * being true.
 *
 * NOT FINAL. This bundle is installed by other packages, and a Doctrine
 * repository is the documented place to add a query — or to stand in for one
 * in somebody else's test. Sealing it would make the seam's query surface the
 * only one its consumers may ever have.
 *
 * @extends ServiceEntityRepository<AreaModule>
 */
class AreaModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AreaModule::class);
    }

    /**
     * Every module assigned to an area, active and parked alike, in sub-nav order.
     *
     * @return list<AreaModule>
     */
    public function forArea(AreaInterface $area): array
    {
        return $this->orderedFor($area, onlyActive: false);
    }

    /**
     * Only the area's active modules, in sub-nav order — what a sub-nav renders.
     *
     * @return list<AreaModule>
     */
    public function activeForArea(AreaInterface $area): array
    {
        return $this->orderedFor($area, onlyActive: true);
    }

    /**
     * @return list<AreaModule>
     */
    private function orderedFor(AreaInterface $area, bool $onlyActive): array
    {
        $qb = $this->createQueryBuilder('am')
            ->join('am.module', 'm')
            ->addSelect('m')
            ->andWhere('am.area = :area')
            ->setParameter('area', $area)
            ->orderBy('am.position', 'ASC')
            ->addOrderBy('am.id', 'ASC');

        if ($onlyActive) {
            $qb->andWhere('am.active = true');
        }

        /** @var list<AreaModule> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }
}
