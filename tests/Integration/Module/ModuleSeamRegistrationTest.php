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

namespace Uhifadhi\Seam\Tests\Integration\Module;

use Uhifadhi\Seam\Tests\Integration\Fixtures\BareModuleProvider;
use Uhifadhi\Seam\Tests\Integration\Fixtures\CollectedModules;
use Uhifadhi\Seam\Tests\Integration\Fixtures\TaggedByHandModuleProvider;
use Uhifadhi\Seam\Tests\Integration\Fixtures\TwoModuleKernel;
use Uhifadhi\Seam\Tests\Integration\SeamKernelTestCase;

/**
 * REGISTRATION, AT THE SEAM ITSELF. "Install the bundle and you are in the
 * catalogue" is two claims: that a provider reaches the seam (here), and that
 * the seam then puts it in the catalogue ({@see \Uhifadhi\Seam\Tests\Integration\Catalogue\ModuleCatalogueTest}).
 * This is the first half, at the tag, before any of it has been read.
 */
final class ModuleSeamRegistrationTest extends SeamKernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TwoModuleKernel::class;
    }

    public function testBothEntrancesToTheSeamArriveInOneCollection(): void
    {
        self::bootKernel();

        /** @var CollectedModules $collected */
        $collected = self::getContainer()->get(CollectedModules::class);
        $modules = $collected->bySlug();

        self::assertArrayHasKey('sightings', $modules, 'an autoconfigured provider is tagged by the seam');
        self::assertArrayHasKey('ferries', $modules, "a module bundle's hand-tagged provider arrives the same way");
        self::assertInstanceOf(BareModuleProvider::class, $modules['sightings']);
        self::assertInstanceOf(TaggedByHandModuleProvider::class, $modules['ferries']);
    }

    /**
     * THE TRAIT DEFAULTS ARE THE CONTRACT'S ANSWER for everything a module did
     * not say, and they have to survive the trip through the container — a
     * module that declares three methods must reach the seam as a live,
     * unpinned, non-base, generically-rendered module with no permissions.
     *
     * base() = false above all: it is the difference between "an area opts into
     * this" and "this is on everywhere", and the safe answer has to be the one
     * you get by saying nothing.
     */
    public function testTheTraitDefaultsSurviveTheTrip(): void
    {
        self::bootKernel();

        /** @var CollectedModules $collected */
        $collected = self::getContainer()->get(CollectedModules::class);
        $module = $collected->bySlug()['sightings'];

        self::assertSame('live', $module->status());
        self::assertNull($module->dataSource());
        self::assertFalse($module->pinned());
        self::assertFalse($module->base(), 'saying nothing must never mean "on in every area"');
        self::assertSame(0, $module->position());
        self::assertNull($module->icon());
        self::assertNull($module->entryRoute(), 'no entry route = the generic module page');
        self::assertSame([], $module->permissions(), 'a module grants nobody anything by existing');
    }

    /**
     * A module that DOES declare base() keeps it. The seam never infers this
     * from a slug it recognises — the flag is the whole mechanism.
     */
    public function testABaseModuleSaysSoItself(): void
    {
        self::bootKernel();

        /** @var CollectedModules $collected */
        $collected = self::getContainer()->get(CollectedModules::class);

        self::assertTrue($collected->bySlug()['ferries']->base());
    }
}
