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

namespace Uhifadhi\Seam\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * THE BOUNDARIES, ENFORCED BY A SWEEP OF src/.
 *
 * These are cheap, crude tests that read the shipped source as text, and they
 * are the only kind that can catch what they catch: an extraction is a large
 * move under time pressure, and "just this one reference, for now" is how a
 * runtime acquires a dependency on a module. They were written before the
 * extraction and their job is to still be green long after it.
 *
 * Three rules:
 *
 *  1. The seam names no module. Not a slug, not a namespace. Everything it
 *     treats specially is a flag a provider declares.
 *  2. The seam names no host. It is installed BY an application; it does not
 *     reach back into one. REDEFINED at the namespace alignment — see
 *     testTheSeamReachesIntoNoHostApplication for what the rule now forbids
 *     and why it cannot be "no Uhifadhi\ in src/" any more.
 *  3. The seam renders nothing. See docs/boundaries.md: the module grid, the
 *     customize screen and every tile is the shell's.
 */
final class BoundaryTest extends TestCase
{
    private const string SRC = __DIR__.'/../../src';

    /**
     * Real modules, plus the two the platform is likeliest to smuggle in:
     * "overview" (the pinned hub — pinned is a flag, not a slug the seam knows)
     * and "map" (the first base module — base is a flag too).
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
    public function testTheSeamKnowsNoModuleByName(string $name): void
    {
        $offenders = [];
        foreach (self::sources() as $path => $code) {
            if (1 === preg_match('/\b'.preg_quote($name, '/').'\b/i', $code)) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders, \sprintf(
            'The seam must not name the "%s" module. A module is whatever tagged itself; '
            .'pinned and base are flags a provider declares, never slugs the runtime recognises.',
            $name,
        ));
    }

    /**
     * The impersonatable HOST TREE. Every subtree an application owns, under
     * either root a host may carry: the product host is `Uhifadhi\`, a project
     * installed from the skeleton is stock-Symfony `App\`. These are exactly
     * the FQCNs a test stub is allowed to impersonate
     * (tests/Integration/Fixtures/Uhifadhi/…) and exactly the ones the shipped
     * runtime must never name.
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
     * The seam is installed by an application and never reaches back into one.
     * A host reference in src/ means the extraction copied a host class instead
     * of moving it.
     *
     * REDEFINED at the namespace alignment. The rule used to read "no
     * `Uhifadhi\` in src/", which worked while the seam shipped as
     * `UhifadhiLabs\Seam\` and `Uhifadhi\` could only mean the application.
     * The seam is `Uhifadhi\Seam\` now, so the root alone proves nothing —
     * what still proves something is the SUBTREE. `Uhifadhi\Seam\Service\…` is
     * the seam's own; `Uhifadhi\Service\…` is the host's, and so is
     * `App\Service\…` in an installed project. The narrowing is a real loss of
     * reach (a host tree not on the list slips through) traded for a rule that is
     * true; the list is cheap to extend when a host grows a tree.
     *
     * @param non-empty-string $namespace
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('hostNamespaces')]
    public function testTheSeamReachesIntoNoHostApplication(string $namespace): void
    {
        $offenders = [];
        foreach (self::sources() as $path => $code) {
            if (str_contains($code, $namespace.'\\')) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders, \sprintf(
            'The seam must not depend on the host application namespace "%s\\".',
            $namespace,
        ));
    }

    /**
     * THE SEAM RENDERS NOTHING. No templates directory, no controllers, no
     * routes — a module grid is a picture of the catalogue, and pictures are the
     * shell's. docs/boundaries.md says why at length; this is the part a refactor
     * cannot quietly disagree with.
     */
    public function testTheSeamShipsNoUserInterface(): void
    {
        self::assertDirectoryDoesNotExist(self::SRC.'/../templates', 'Templates belong to the shell.');
        self::assertDirectoryDoesNotExist(self::SRC.'/Controller', 'Controllers belong to the shell.');

        $offenders = [];
        foreach (self::sources() as $path => $code) {
            if (str_contains($code, 'Symfony\\Component\\Routing\\Attribute\\Route')
                || str_contains($code, 'AbstractController')
                || str_contains($code, '.html.twig')) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders, 'The seam exposes data and services; it draws nothing.');
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
