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

namespace Uhifadhi\Seam\Tests\Integration;

use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\MappingException;

/**
 * FROM `composer require` TO TABLES — the two claims the seam's recipe makes
 * about that road, pinned here so the documentation cannot quietly become a
 * lie.
 *
 * Both were found the same way: by installing a project with
 * `composer create-project` and following the instructions as written. Neither
 * survived the walk, and the recipe's comments now say what these tests say.
 *
 * 1. THE AREA MAPPING IS REQUIRED, NOT OPTIONAL. The recipe used to describe
 *    `resolve_target_entities` as the step that buys you the per-area half,
 *    implying an installation without it simply had less. It has less *and* no
 *    schema at all: every tool that resolves the association stops on the
 *    unresolved interface. The claim under test is the honest one — a bundle
 *    that boots without the mapping and cannot be schema'd without it.
 *
 * 2. THE TOOL THAT CREATES THOSE TABLES SHIPS WITH THE BUNDLE THAT ADDS THEM.
 *    An installed project had no `doctrine:migrations:*` commands, because
 *    nothing in the chain required the bundle — the seam contributed two
 *    tables and left the operator to discover there was nothing to create them
 *    with.
 */
final class InstallabilityTest extends SeamKernelTestCase
{
    /**
     * The seam alone boots. That is the state a host is in between
     * `composer require` and writing its area entity, and it must not be a
     * broken one.
     */
    public function testTheSeamAloneBootsWithoutAnAreaMapping(): void
    {
        self::bootKernel();

        self::assertInstanceOf(EntityManagerInterface::class, $this->entityManager());
    }

    /**
     * …and cannot be given a schema, naming the interface as it refuses.
     *
     * The message matters as much as the failure: it is the string an operator
     * pastes into a search box, and it is quoted verbatim in
     * `config/packages/seam.yaml` so that the answer is in the file the error
     * is about.
     */
    public function testWithoutTheAreaMappingThereIsNoSchemaToCreate(): void
    {
        self::bootKernel();

        $em = $this->entityManager();
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Class 'Uhifadhi\\Seam\\Entity\\AreaInterface' does not exist");

        new SchemaTool($em)->getCreateSchemaSql($metadata);
    }

    /**
     * The migrations bundle is the seam's dependency, not the host's homework.
     *
     * A bundle that contributes tables owns the need for a migration tool the
     * same way it owns the need for the ORM — which the seam has always
     * required. What the seam deliberately does NOT ship is migration
     * versions: the tables are the seam's, but the migration history belongs
     * to the installation, and a vendor replaying its own versions into it
     * would fight every `doctrine:migrations:diff` the host ever runs.
     */
    public function testTheMigrationToolArrivesWithTheRingThatAddsTables(): void
    {
        self::assertTrue(
            class_exists(DoctrineMigrationsBundle::class),
            'an installed project gets doctrine:migrations:* because the seam requires the bundle',
        );

        self::assertSame(
            [],
            glob(\dirname(__DIR__, 2).'/migrations/*.php') ?: [],
            'and the seam ships no versions of its own: the history is the host\'s',
        );
    }

    private function entityManager(): EntityManagerInterface
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($em instanceof EntityManagerInterface);

        return $em;
    }
}
