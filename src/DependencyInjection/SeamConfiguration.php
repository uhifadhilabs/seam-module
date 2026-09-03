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

namespace Uhifadhi\Seam\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * The bundle's semantic configuration — how a host configures the seam runtime
 * in config/packages/seam.yaml:
 *
 *   seam:
 *     default_category: operations   # where an unplaced module is filed
 *     dev_tools: false               # dev-only tooling (when@dev / when@test)
 *
 * DELIBERATELY TINY, and it should stay that way. The seam's job is to carry
 * what modules declare; nearly everything a deployment might want to say is
 * said by a module's own config, not here. There is no key for "which modules
 * exist" and there never will be — installing the bundle IS the declaration.
 *
 * Static so the tree is testable with a plain Processor and shared verbatim by
 * the bundle's configure().
 */
final class SeamConfiguration
{
    public static function define(NodeDefinition|ArrayNodeDefinition $root): void
    {
        if (!$root instanceof ArrayNodeDefinition) {
            throw new \LogicException('The seam root node must be an array node.');
        }

        $root
            ->children()
                ->scalarNode('default_category')
                    ->info('Catalogue category an unrecognised provider category is coerced to.')
                    ->defaultValue('operations')->cannotBeEmpty()
                ->end()
                ->booleanNode('dev_tools')
                    ->info('Register dev-only tooling (seeders, fixtures). The recipe enables this via when@dev/when@test.')
                    ->defaultFalse()
                ->end()
            ->end()
        ;
    }
}
