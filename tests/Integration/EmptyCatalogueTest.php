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

use Uhifadhi\Seam\Tests\Integration\Fixtures\CollectedModules;
use Uhifadhi\Seam\UhifadhiSeamBundle;

/**
 * THE GROWTH STEP: seed + seam, and no modules at all.
 *
 * This is what a real installation looks like the moment after
 * `composer require uhifadhi/seam-module` and before the first branch — and it
 * has to be a boring, working, EMPTY installation: the container compiles, the
 * seam exists, and it carries nothing. A runtime that only works once somebody
 * installs a module is a runtime with a hidden dependency on modules, and the
 * whole point of a seam is that the branches attach to it rather than the
 * other way round.
 *
 * Zero is a real number of modules. It is also the first one every installation
 * has.
 */
final class EmptyCatalogueTest extends SeamKernelTestCase
{
    public function testAnInstallationWithNoModulesBootsAndCarriesNothing(): void
    {
        self::bootKernel();

        /** @var CollectedModules $collected */
        $collected = self::getContainer()->get(CollectedModules::class);

        self::assertSame([], $collected->all(), 'no modules installed, so nothing is registered');
        self::assertSame([], $collected->bySlug());
    }

    /**
     * THE SEAM IS NOT A MODULE. It contributes no tile, no category and no
     * catalogue row of its own — it is the thing rows live in. A runtime that
     * registered itself would appear in every area's module grid as a
     * capability nobody can use.
     *
     * This is also what makes the assertion above meaningful: "empty" means
     * empty, not "empty apart from us".
     */
    public function testTheSeamRegistersNoModuleOfItsOwn(): void
    {
        $kernel = self::bootKernel();

        self::assertArrayHasKey('UhifadhiSeamBundle', $kernel->getBundles(), 'the seam is installed');

        /** @var CollectedModules $collected */
        $collected = self::getContainer()->get(CollectedModules::class);
        self::assertCount(0, $collected->all(), 'and it registered no module while it was at it');
    }

    /**
     * The tag string is published as a constant on the bundle, because the
     * seam is the end that collects it and a module bundle types the other end
     * by hand. Renaming it is a breaking change for every installed module, so
     * the value is pinned here rather than left to a refactor.
     */
    public function testTheModuleTagNameIsPartOfThePublishedContract(): void
    {
        self::assertSame('uhifadhi.module', UhifadhiSeamBundle::MODULE_TAG);
    }
}
