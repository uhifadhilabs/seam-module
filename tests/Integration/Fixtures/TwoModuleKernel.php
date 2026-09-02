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

namespace UhifadhiLabs\Trunk\Tests\Integration\Fixtures;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use UhifadhiLabs\Trunk\Tests\Integration\TestKernel;
use UhifadhiLabs\Trunk\UhifadhiLabsTrunkBundle;

/**
 * The trunk with two modules on it, arriving by each of the seam's two
 * entrances:
 *
 *  - {@see BareModuleProvider} is AUTOCONFIGURED, the way a module defined by
 *    the host application itself is, and gets the tag from the trunk's
 *    registerForAutoconfiguration();
 *  - {@see TaggedByHandModuleProvider} stands in for an installed module
 *    BUNDLE, whose services are never autoconfigured and which therefore writes
 *    the tag itself.
 *
 * Both must land in the same collection, or "install the bundle and you are
 * registered" is only true for half the modules.
 */
final class TwoModuleKernel extends TestKernel
{
    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);

        $services = $container->services();

        $services->set(BareModuleProvider::class)
            ->autoconfigure();

        $services->set(TaggedByHandModuleProvider::class)
            ->tag(UhifadhiLabsTrunkBundle::MODULE_TAG);
    }
}
