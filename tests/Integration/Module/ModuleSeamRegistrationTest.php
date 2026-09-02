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

namespace UhifadhiLabs\Trunk\Tests\Integration\Module;

use UhifadhiLabs\Trunk\Tests\Integration\Fixtures\BareModuleProvider;
use UhifadhiLabs\Trunk\Tests\Integration\Fixtures\CollectedModules;
use UhifadhiLabs\Trunk\Tests\Integration\Fixtures\TaggedByHandModuleProvider;
use UhifadhiLabs\Trunk\Tests\Integration\Fixtures\TwoModuleKernel;
use UhifadhiLabs\Trunk\Tests\Integration\TrunkKernelTestCase;

/**
 * REGISTRATION, AT THE SEAM ITSELF. "Install the bundle and you are in the
 * catalogue" is two claims: that a provider reaches the trunk (here), and that
 * the trunk then puts it in the catalogue (tests/Phase2, red until the
 * extraction). This half is green today, because the tag and its
 * autoconfiguration moved into this bundle with it.
 */
final class ModuleSeamRegistrationTest extends TrunkKernelTestCase
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

        self::assertArrayHasKey('sightings', $modules, 'an autoconfigured provider is tagged by the trunk');
        self::assertArrayHasKey('ferries', $modules, "a module bundle's hand-tagged provider arrives the same way");
        self::assertInstanceOf(BareModuleProvider::class, $modules['sightings']);
        self::assertInstanceOf(TaggedByHandModuleProvider::class, $modules['ferries']);
    }

    /**
     * THE TRAIT DEFAULTS ARE THE CONTRACT'S ANSWER for everything a module did
     * not say, and they have to survive the trip through the container — a
     * module that declares three methods must reach the trunk as a live,
     * unpinned, non-core, generically-rendered module with no permissions.
     *
     * core() = false above all: it is the difference between "an area opts into
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
        self::assertFalse($module->core(), 'saying nothing must never mean "on in every area"');
        self::assertSame(0, $module->position());
        self::assertNull($module->icon());
        self::assertNull($module->entryRoute(), 'no entry route = the generic module page');
        self::assertSame([], $module->permissions(), 'a module grants nobody anything by existing');
    }

    /**
     * A module that DOES declare core() keeps it. The trunk never infers this
     * from a slug it recognises — the flag is the whole mechanism.
     */
    public function testACoreModuleSaysSoItself(): void
    {
        self::bootKernel();

        /** @var CollectedModules $collected */
        $collected = self::getContainer()->get(CollectedModules::class);

        self::assertTrue($collected->bySlug()['ferries']->core());
    }
}
