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

namespace Uhifadhi\Seam;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Uhifadhi\ModuleContracts\ModuleProviderInterface;
use Uhifadhi\Seam\DependencyInjection\SeamConfiguration;

/**
 * The SEAM — the runtime every uhifadhi module registers with.
 *
 * The skeleton is the application, the seam carries the modules, and the shell
 * is what you see. This bundle is the carrying: the module catalogue, the
 * per-area record of what is switched on, the permissions modules declare, and
 * the seed command that keeps the catalogue in step with what is installed.
 *
 * IT RENDERS NOTHING. No templates, no controllers, no routes — the visible
 * surface is the shell's job. The seam answers questions in data and services,
 * and anything that draws a module grid reads it. See the README's boundaries
 * section for why the grid is not here.
 *
 * IT KNOWS NO MODULE BY NAME — not one, not even the pinned hub every
 * installation has. A module is whatever tagged itself, and everything the
 * seam treats specially (pinned, base) is a flag the provider declares, never
 * a slug the runtime recognises. A test sweeps src/ for that property, and the
 * sweep is why this paragraph names nothing either.
 *
 * THIS CLASS IS THE PLUG: the bundle registers, its config is keyed under
 * "seam:", its entity directory is mapped, and it autoconfigures the module
 * tag. The runtime itself lives in src/Service and src/Repository.
 */
final class UhifadhiSeamBundle extends AbstractBundle
{
    /**
     * The tag every module provider carries. Published as a constant because
     * the seam is the end that COLLECTS it — a module bundle writes the string
     * by hand in its own extension (it is not autoconfigured), and a host or a
     * test that wants to stand in for the collector should not retype it.
     */
    public const string MODULE_TAG = 'uhifadhi.module';

    /** Config lives under "seam:", not the class-derived "uhifadhi_seam:". */
    protected string $extensionAlias = 'seam';

    public function configure(DefinitionConfigurator $definition): void
    {
        SeamConfiguration::define($definition->rootNode());
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // THE SEAM, AT ITS COLLECTING END. Every ModuleProviderInterface gets
        // the tag, so a module the host itself defines is collected exactly like
        // a module bundle's. This is autoconfiguration, which only fires for
        // autoconfigured services — a reusable bundle's own services are not, so
        // a module BUNDLE still writes the tag by hand. Both ends meet here.
        //
        // The autoconfiguration lives with the collector, not in the
        // application's Kernel, so a fresh installation plus this bundle is
        // already a working seam.
        $container->registerForAutoconfiguration(ModuleProviderInterface::class)
            ->addTag(self::MODULE_TAG);
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Zero-config persistence: the seam maps its own entities, so a host
        // never writes a doctrine mappings block for the catalogue tables.
        if ($builder->hasExtension('doctrine')) {
            $container->extension('doctrine', [
                'orm' => [
                    'mappings' => [
                        'UhifadhiSeam' => [
                            'type' => 'attribute',
                            'dir' => __DIR__.'/Entity',
                            'prefix' => 'Uhifadhi\\Seam\\Entity',
                            'is_bundle' => false,
                        ],
                    ],
                ],
            ]);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Static service wiring lives in a PHP config file (see config/services.php
        // for why PHP, not YAML). loadExtension keeps only the config-DRIVEN bits.
        $container->import('../config/services.php');

        // The category an unplaced module falls back to. A provider naming a
        // category the deployment does not have is coerced rather than trusted,
        // and this is what it is coerced TO — "operations" by default, because
        // this is an operations platform and an unplaced module is far likelier
        // to be somebody's daily work than a reading of the ecosystem.
        $builder->setParameter(
            'seam.default_category',
            \is_string($config['default_category'] ?? null) ? $config['default_category'] : 'operations',
        );

        // Dev-only tooling (seeders, fixtures) hangs off this flag, so a
        // production installation never grows a command that writes invented
        // catalogue rows. Nothing claims it yet — the switch exists so the first
        // thing that needs it has somewhere to hang.
        $builder->setParameter('seam.dev_tools', true === ($config['dev_tools'] ?? false));
    }
}
