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

namespace Uhifadhi\Trunk\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\Mapping\MappingDriver as DoctrineBundleMappingDriver;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Uhifadhi\Trunk\Entity\AreaModule;
use Uhifadhi\Trunk\Entity\Module;
use Uhifadhi\Trunk\UhifadhiTrunkBundle;

/**
 * The smoke test: registering the trunk in a real kernel compiles a real
 * container. Everything else in this repository — and every module that ever
 * registers with the seam — rides on that.
 */
final class BundleBootTest extends TrunkKernelTestCase
{
    public function testTheBundleBootsInAHostKernel(): void
    {
        $kernel = self::bootKernel();

        self::assertArrayHasKey('UhifadhiTrunkBundle', $kernel->getBundles());
        self::assertInstanceOf(
            UhifadhiTrunkBundle::class,
            $kernel->getBundle('UhifadhiTrunkBundle'),
        );
    }

    /**
     * Config lives under "trunk:", not the class-derived "uhifadhi_labs_trunk:"
     * — the alias is part of the host contract and every installation writes it.
     */
    public function testItsConfigurationIsKeyedByTheTrunkAlias(): void
    {
        $kernel = self::bootKernel();

        self::assertSame('trunk', $kernel->getBundle('UhifadhiTrunkBundle')
            ->getContainerExtension()?->getAlias());
    }

    /**
     * The configured defaults reach the container, which is where the runtime
     * will read them from. "operations" is the wave-1 ruling: an unplaced module
     * is somebody's daily work, not a reading of the ecosystem.
     */
    public function testItsDefaultsReachTheContainer(): void
    {
        self::bootKernel();

        self::assertSame('operations', self::getContainer()->getParameter('trunk.default_category'));
        self::assertFalse(self::getContainer()->getParameter('trunk.dev_tools'));
    }

    /**
     * Zero-config persistence: the trunk maps its own entity directory, so a
     * host never writes a doctrine mappings block for the catalogue tables —
     * `composer require` is the whole of the installation.
     */
    public function testItMapsItsOwnEntityDirectory(): void
    {
        self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        /** @var EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $driver = $em->getConfiguration()->getMetadataDriverImpl();
        // DoctrineBundle decorates the chain (custom id-generator support);
        // the namespace registry lives on the chain underneath.
        if ($driver instanceof DoctrineBundleMappingDriver) {
            $driver = $driver->getDriver();
        }

        self::assertInstanceOf(MappingDriverChain::class, $driver);
        self::assertArrayHasKey('Uhifadhi\Trunk\Entity', $driver->getDrivers());
        // And the catalogue is what comes out of it — mapped by a bundle the
        // host did nothing to configure beyond installing it. Note the kernel
        // this runs on: the trunk alone, with no host resolving the area
        // interface, which a host that has not got there yet must still boot.
        // Canonicalising, not ordering: what comes out of the driver is whatever
        // order the directory was read in, which is the filesystem's business and
        // not a promise this bundle makes. The claim is that both are mapped.
        self::assertEqualsCanonicalizing(
            [Module::class, AreaModule::class],
            array_map(
                static fn (ClassMetadata $metadata): string => $metadata->getName(),
                $em->getMetadataFactory()->getAllMetadata(),
            ),
        );
    }
}
