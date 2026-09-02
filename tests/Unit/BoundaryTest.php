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

namespace UhifadhiLabs\Trunk\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * THE BOUNDARIES, ENFORCED BY A SWEEP OF src/.
 *
 * These are cheap, crude tests that read the shipped source as text, and they
 * are the only kind that can catch what they catch: an extraction is a large
 * move under time pressure, and "just this one reference, for now" is how a
 * runtime acquires a dependency on a branch. They are green today, and their
 * job is to be green the day AFTER phase 2 as well.
 *
 * Three rules:
 *
 *  1. The trunk names no module. Not a slug, not a namespace. Everything it
 *     treats specially is a flag a provider declares.
 *  2. The trunk names no host. It is installed BY an application; it does not
 *     reach back into one.
 *  3. The trunk renders nothing. See the README's boundaries section: the
 *     module grid, the customize screen and every tile is the canopy's.
 */
final class BoundaryTest extends TestCase
{
    private const string SRC = __DIR__.'/../../src';

    /**
     * Real modules, plus the two the platform is likeliest to smuggle in:
     * "overview" (the pinned hub — pinned is a flag, not a slug the trunk knows)
     * and "map" (the first core module — core is a flag too).
     */
    public static function moduleNames(): \Generator
    {
        foreach (['patrol', 'incident', 'roster', 'ingestion', 'storage', 'workflow', 'uhakiki', 'forest', 'overview'] as $name) {
            yield $name => [$name];
        }
    }

    /**
     * @param non-empty-string $name
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('moduleNames')]
    public function testTheTrunkKnowsNoModuleByName(string $name): void
    {
        $offenders = [];
        foreach (self::sources() as $path => $code) {
            if (1 === preg_match('/\b'.preg_quote($name, '/').'\b/i', $code)) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders, \sprintf(
            'The trunk must not name the "%s" module. A module is whatever tagged itself; '
            .'pinned and core are flags a provider declares, never slugs the runtime recognises.',
            $name,
        ));
    }

    /**
     * The trunk is installed by an application and never reaches back into one.
     * A `Uhifadhi\` reference in src/ means the extraction copied a host class
     * instead of moving it.
     */
    public function testTheTrunkReachesIntoNoHostApplication(): void
    {
        $offenders = [];
        foreach (self::sources() as $path => $code) {
            if (str_contains($code, 'Uhifadhi\\Entity') || str_contains($code, 'Uhifadhi\\Service') || str_contains($code, 'Uhifadhi\\Repository')) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders, 'The trunk must not depend on a host application namespace.');
    }

    /**
     * THE TRUNK RENDERS NOTHING. No templates directory, no controllers, no
     * routes — a module grid is a picture of the catalogue, and pictures are the
     * canopy's. The README says why at length; this is the part a refactor
     * cannot quietly disagree with.
     */
    public function testTheTrunkShipsNoUserInterface(): void
    {
        self::assertDirectoryDoesNotExist(self::SRC.'/../templates', 'Templates belong to the canopy.');
        self::assertDirectoryDoesNotExist(self::SRC.'/Controller', 'Controllers belong to the canopy.');

        $offenders = [];
        foreach (self::sources() as $path => $code) {
            if (str_contains($code, 'Symfony\\Component\\Routing\\Attribute\\Route')
                || str_contains($code, 'AbstractController')
                || str_contains($code, '.html.twig')) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders, 'The trunk exposes data and services; it draws nothing.');
    }

    /**
     * @return array<string, string> relative path => source
     */
    private static function sources(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::SRC, \FilesystemIterator::SKIP_DOTS),
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ('php' !== $file->getExtension()) {
                continue;
            }
            $code = file_get_contents($file->getPathname());
            if (false === $code) {
                continue;
            }
            $files['src/'.substr($file->getPathname(), \strlen(self::SRC) + 1)] = $code;
        }

        ksort($files);

        return $files;
    }
}
