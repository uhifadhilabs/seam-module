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

namespace Uhifadhi\Trunk\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * THE BOUNDARIES, ENFORCED BY A SWEEP OF src/.
 *
 * These are cheap, crude tests that read the shipped source as text, and they
 * are the only kind that can catch what they catch: an extraction is a large
 * move under time pressure, and "just this one reference, for now" is how a
 * runtime acquires a dependency on a branch. They were written before the
 * extraction and their job is to still be green long after it.
 *
 * Three rules:
 *
 *  1. The trunk names no module. Not a slug, not a namespace. Everything it
 *     treats specially is a flag a provider declares.
 *  2. The trunk names no host. It is installed BY an application; it does not
 *     reach back into one. REDEFINED at the namespace alignment — see
 *     testTheTrunkReachesIntoNoHostApplication for what the rule now forbids
 *     and why it cannot be "no Uhifadhi\ in src/" any more.
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
     * The impersonatable HOST TREE. Every subtree an application owns, under
     * either root a host may carry: the product host is `Uhifadhi\`, a freshly
     * planted seed is stock-Symfony `App\`. These are exactly the FQCNs a test
     * stub is allowed to impersonate (tests/Integration/Fixtures/Uhifadhi/…) and
     * exactly the ones the shipped runtime must never name.
     *
     * @return \Generator<string, array{string}>
     */
    public static function hostNamespaces(): \Generator
    {
        foreach (['Uhifadhi', 'App'] as $root) {
            foreach (['Entity', 'Service', 'Repository', 'Controller', 'Module', 'Overview'] as $tree) {
                $fqcn = $root.'\\'.$tree;
                yield $fqcn => [$fqcn];
            }
        }
    }

    /**
     * The trunk is installed by an application and never reaches back into one.
     * A host reference in src/ means the extraction copied a host class instead
     * of moving it.
     *
     * REDEFINED at the namespace alignment. The rule used to read "no
     * `Uhifadhi\` in src/", which worked while the trunk shipped as
     * `UhifadhiLabs\Trunk\` and `Uhifadhi\` could only mean the application.
     * The trunk is `Uhifadhi\Trunk\` now, so the root alone proves nothing —
     * what still proves something is the SUBTREE. `Uhifadhi\Trunk\Service\…` is
     * the trunk's own; `Uhifadhi\Service\…` is the host's, and so is
     * `App\Service\…` in a planted seed. The narrowing is a real loss of reach
     * (a host tree not on the list slips through) traded for a rule that is
     * true; the list is cheap to extend when a host grows a tree.
     *
     * @param non-empty-string $namespace
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('hostNamespaces')]
    public function testTheTrunkReachesIntoNoHostApplication(string $namespace): void
    {
        $offenders = [];
        foreach (self::sources() as $path => $code) {
            if (str_contains($code, $namespace.'\\')) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders, \sprintf(
            'The trunk must not depend on the host application namespace "%s\\".',
            $namespace,
        ));
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
