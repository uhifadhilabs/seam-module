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

namespace UhifadhiLabs\Trunk\Tests\Unit\Catalogue;

use PHPUnit\Framework\TestCase;
use UhifadhiLabs\Trunk\Enum\ModuleCategory;
use UhifadhiLabs\Trunk\Enum\ModuleStatus;
use UhifadhiLabs\Trunk\Service\ProviderCatalogueMapper;
use UhifadhiLabs\Trunk\Tests\Integration\Fixtures\SpecModuleProvider;

/**
 * SPEC 4 — CATEGORY MAPPING, AND WHAT AN UNPLACED MODULE IS.
 *
 * A provider's category and status are strings the trunk COERCES rather than
 * trusts: a module bundle is written by someone else, ships on its own release
 * cadence, and must not be able to break the catalogue for every other module
 * with a typo. The coercion is silent by design — the module's tile still
 * appears, filed somewhere sensible — which is also its cost, so the fallback
 * has to be the one that is least wrong.
 *
 * Operations is that fallback (the wave-1 ruling). This is an operations
 * platform: a module the catalogue cannot place is far likelier to be somebody's
 * daily work than a reading of the ecosystem, and the old fallback ("pressure")
 * said the opposite — that the module measures what people are doing TO the
 * area.
 *
 * Pure mapping, no container, no database.
 */
final class ProviderCatalogueMapperTest extends TestCase
{
    private function mapper(string $default = 'operations'): ProviderCatalogueMapper
    {
        return new ProviderCatalogueMapper($default);
    }

    public function testAProviderBecomesACatalogueRow(): void
    {
        $row = $this->mapper()->toRow(new SpecModuleProvider('sightings', [
            'name' => 'Sightings',
            'category' => 'biodiversity',
            'status' => 'template',
            'source' => 'Field observations',
            'icon' => 'binoculars',
        ]), 20);

        self::assertSame('sightings', $row['slug']);
        self::assertSame('Sightings', $row['name']);
        self::assertSame(ModuleCategory::Biodiversity, $row['category']);
        self::assertSame(ModuleStatus::Template, $row['status']);
        self::assertSame('Field observations', $row['source']);
        self::assertSame('binoculars', $row['icon']);
        self::assertFalse($row['pinned']);
        self::assertSame(20, $row['position']);
    }

    /**
     * THE FOURTH CATEGORY. Operations was added after the three readings of the
     * area (flux, pressure, biodiversity), and after the operational pivot it is
     * the one most modules belong to — the rangers' own work had nowhere to go
     * before it.
     */
    public function testAModuleMayFileItselfUnderOperations(): void
    {
        $row = $this->mapper()->toRow(new SpecModuleProvider('ferries', ['category' => 'operations']), 0);

        self::assertSame(ModuleCategory::Operations, $row['category']);
        self::assertSame('Operations', $row['category']->label());
    }

    public function testAnUnknownCategoryFallsBackToOperations(): void
    {
        $row = $this->mapper()->toRow(new SpecModuleProvider('tides', ['category' => 'hydrology']), 0);

        self::assertSame(ModuleCategory::Operations, $row['category']);
    }

    public function testAnUnknownStatusFallsBackToLive(): void
    {
        $row = $this->mapper()->toRow(new SpecModuleProvider('tides', ['status' => 'experimental']), 0);

        self::assertSame(ModuleStatus::Live, $row['status'], 'A module that is installed is running.');
    }

    /**
     * The fallback is the deployment's, not the mapper's. A deployment that
     * configures `trunk.default_category` is telling the trunk where its
     * unplaced modules belong, and the mapper must read it rather than hardcode
     * the platform default a second time.
     */
    public function testTheFallbackIsWhateverTheDeploymentConfigured(): void
    {
        $row = $this->mapper('flux')->toRow(new SpecModuleProvider('tides', ['category' => 'hydrology']), 0);

        self::assertSame(ModuleCategory::Flux, $row['category']);
    }

    /**
     * SPEC 3, THE SEEDING HALF. An installable module arrives PARKED, so an
     * admin opts it in per area — right for a capability an area may not want.
     * A core module arrives ACTIVE, because it is machinery other surfaces
     * already import and an area with it off does not have fewer features, it
     * has broken screens.
     *
     * The flag decides the INITIAL state and nothing else. Core is not pinned
     * and not permanent: the customize screen still governs the area afterwards,
     * and the seed is create-only, so an admin who later switches a core module
     * off is not overruled on the next deploy.
     */
    public function testAnInstallableModuleArrivesParkedAndACoreModuleArrivesActive(): void
    {
        self::assertFalse($this->mapper()->toRow(new SpecModuleProvider('sightings'), 0)['active']);

        $core = $this->mapper()->toRow(new SpecModuleProvider('ferries', ['core' => true]), 0);
        self::assertTrue($core['active'], 'machinery other surfaces depend on is not an opt-in');
        self::assertFalse($core['pinned'], 'core is the initial state; pinned is the ordering — separate questions');
    }

    /**
     * A KNOWN WART, SPECIFIED AWAY.
     *
     * `ModuleProviderInterface::position()` is documented as "ordering hint
     * within the catalogue; lower sorts first", and in the host TODAY nothing
     * reads it: the seed passes its own loop index and the provider's answer is
     * discarded. A contract method nothing reads is a lie in the contract, and a
     * module author who sets it has no way to discover that it did nothing.
     *
     * The trunk honours it: a declared position wins, and registration order is
     * only the tie-break for the modules that declared none (which, thanks to
     * the trait default of 0, is most of them — so the common case is unchanged).
     */
    public function testADeclaredPositionIsHonouredRatherThanDiscarded(): void
    {
        $row = $this->mapper()->toRow(new SpecModuleProvider('sightings', ['position' => 5]), 20);

        self::assertSame(5, $row['position'], 'a module that declares where it sorts is not overruled by loop order');
    }

    public function testAModuleThatDeclaresNoPositionTakesItsRegistrationOrder(): void
    {
        $row = $this->mapper()->toRow(new SpecModuleProvider('sightings'), 20);

        self::assertSame(20, $row['position']);
    }
}
