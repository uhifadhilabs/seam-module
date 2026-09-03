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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Uhifadhi\Entity\AreaOfInterest;
use Uhifadhi\Seam\Tests\Integration\Fixtures\HostKernel;

/**
 * Shared plumbing for the specifications that need a database and a set of
 * installed modules.
 *
 * The database is the fundi cluster's `seam_bundle_test` (port 5434). Unlike
 * its sibling bundles the seam genuinely owns tables — the catalogue and the
 * per-area install record are its data, not the host's — so there is no honest
 * version of this suite that avoids a connection.
 */
abstract class InstallationTestCase extends SeamKernelTestCase
{
    protected static function getKernelClass(): string
    {
        return HostKernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        HostKernel::$modules = [];
    }

    protected function tearDown(): void
    {
        HostKernel::$modules = [];

        parent::tearDown();
    }

    /**
     * INSTALL THESE MODULES AND SEED — the whole install path, in one line:
     * boot an installation carrying exactly these module bundles, give it a
     * schema, and run the catalogue seed the way `composer require` tells an
     * operator to.
     *
     * Called a second time in one test, it is an UNINSTALL as well as an
     * install: the modules not named are the ones whose bundles were removed,
     * and the database survives the reboot exactly as a real one does.
     *
     * @param array<string, array<string, mixed>>|list<string> $modules slug => provider overrides, or bare slugs
     */
    protected function install(array $modules, bool $freshDatabase = true): void
    {
        $normalised = [];
        foreach ($modules as $key => $value) {
            if (\is_int($key)) {
                \assert(\is_string($value));
                $normalised[$value] = [];

                continue;
            }
            \assert(\is_array($value));
            $normalised[$key] = $value;
        }

        self::ensureKernelShutdown();
        HostKernel::$modules = $normalised;
        self::bootKernel();

        if ($freshDatabase) {
            $metadata = $this->em()->getMetadataFactory()->getAllMetadata();
            $tool = new SchemaTool($this->em());
            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
        }

        $this->seed();
    }

    /**
     * Run the catalogue seed. The command is `seam:catalogue:seed` — a bundle's
     * command belongs in its own namespace — and it keeps the host's
     * `app:seed:catalogue` as an alias, because that string is written down in
     * the deploy pipeline and in every module's README, and an extraction that
     * silently renames it breaks a deploy rather than a test.
     */
    protected function seed(): CommandTester
    {
        $kernel = self::$kernel;
        \assert(null !== $kernel);

        $tester = new CommandTester(new Application($kernel)->find('seam:catalogue:seed'));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $this->em()->clear();

        return $tester;
    }

    protected function em(): EntityManagerInterface
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($em instanceof EntityManagerInterface);

        return $em;
    }

    protected function area(string $name = 'Test area'): AreaOfInterest
    {
        $area = new AreaOfInterest()->setName($name);
        $this->em()->persist($area);
        $this->em()->flush();

        return $area;
    }

    protected function service(string $id): object
    {
        return self::getContainer()->get('test.'.$id);
    }
}
