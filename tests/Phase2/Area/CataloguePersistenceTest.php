<?php

declare(strict_types=1);

/*
 * This file is part of the UhifadhiLabs Trunk Module.
 *
 * (c) Ezekiel Mjema <https://github.com/eemjema>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace UhifadhiLabs\Trunk\Tests\Phase2\Area;

use Doctrine\ORM\Mapping\ClassMetadata;
use UhifadhiLabs\Trunk\Area\AreaInterface;
use UhifadhiLabs\Trunk\Entity\AreaModule;
use UhifadhiLabs\Trunk\Entity\Module;
use UhifadhiLabs\Trunk\Tests\Phase2\Phase2TestCase;

/**
 * WHAT THE EXTRACTION MOVES, AND WHAT IT MUST NOT RENAME.
 *
 * The trunk takes ownership of two tables that already exist, already hold
 * production data, and are already the most-referenced tables in the platform.
 * That makes this specification unusual: most of it is about things staying
 * exactly as they are.
 *
 * TABLE NAMES ARE NOT COSMETIC. The module guide has bundles prefix their
 * tables with their domain word (`sightings_observation`), and by that rule
 * these would become `trunk_module` and `trunk_area_module`. They do not. A
 * rename here is a production migration on the platform's central tables, paid
 * for in downtime and risk, bought with nothing but consistency — and the
 * prefix rule exists to stop two bundles colliding on a common noun, which
 * cannot happen to the runtime every bundle registers with. The convention is
 * followed by every branch and deliberately not by the trunk; this test is that
 * decision, written where a refactor will trip over it.
 *
 * THE AREA IS THE HOST'S. An AreaModule points at an area, and the host owns
 * areas — so the trunk maps the association to its own interface and the host
 * resolves it (see the AreaOfInterest fixture for why not a bare id).
 */
final class CataloguePersistenceTest extends Phase2TestCase
{
    /**
     * @return ClassMetadata<object>
     */
    private function metadata(string $class): ClassMetadata
    {
        return $this->em()->getClassMetadata($class);
    }

    public function testTheCatalogueTablesKeepTheNamesTheProductionDataIsIn(): void
    {
        $this->install([]);

        self::assertSame('module', $this->metadata(Module::class)->getTableName());
        self::assertSame('area_module', $this->metadata(AreaModule::class)->getTableName());
    }

    /**
     * A module's identity is its slug, and it is unique across the deployment —
     * the seed upserts on it and the routing keys on it, so a duplicate is not a
     * data problem, it is two modules answering to one URL.
     */
    public function testAModulesSlugIsItsIdentity(): void
    {
        $this->install([]);

        $slug = $this->metadata(Module::class)->getFieldMapping('slug');

        self::assertTrue($slug->unique ?? false);
    }

    /**
     * One row per area per module. Without the constraint, a double-submitted
     * customize form is how an area's sub-nav grows a duplicate tab — and the
     * database is the only place that can be made impossible rather than
     * unlikely.
     */
    public function testAnAreaHoldsOneRowPerModule(): void
    {
        $this->install([]);

        $constraints = $this->metadata(AreaModule::class)->table['uniqueConstraints'] ?? [];

        self::assertNotSame([], $constraints, 'the (area, module) pair is unique in the database, not just in the service');
    }

    /**
     * THE AREA SEAM. The trunk maps its association to an interface it owns; a
     * host resolves that interface to its own area entity. This is what lets the
     * trunk hold the per-area table without requiring — or defining — the host's
     * area model.
     */
    public function testTheAreaAssociationIsResolvedToTheHostsOwnEntity(): void
    {
        $this->install([]);

        $association = $this->metadata(AreaModule::class)->getAssociationMapping('area');

        self::assertSame(\Uhifadhi\Entity\AreaOfInterest::class, $association->targetEntity,
            'the host resolved the trunk\'s area interface to its own entity');
        self::assertTrue(interface_exists(AreaInterface::class),
            'and the interface the trunk maps is the trunk\'s to publish');
    }

    /**
     * Deleting an area takes its module assignments with it. An orphaned row
     * pointing at an area that no longer exists is the failure mode the
     * association was chosen over a bare id to prevent.
     */
    public function testDeletingAnAreaTakesItsAssignmentsWithIt(): void
    {
        $this->install(['sightings' => ['core' => true]]);
        $area = $this->area();
        $this->seed();

        $this->em()->remove($this->em()->find(\Uhifadhi\Entity\AreaOfInterest::class, $area->getId()));
        $this->em()->flush();

        self::assertSame(
            0,
            (int) $this->em()->createQuery('SELECT COUNT(am) FROM '.AreaModule::class.' am')->getSingleScalarResult(),
        );
    }
}
