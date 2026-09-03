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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Uhifadhi\Seam\Command\SeedCatalogueCommand;
use Uhifadhi\Seam\Repository\AreaModuleRepository;
use Uhifadhi\Seam\Repository\ModuleRepository;
use Uhifadhi\Seam\Service\AreaModuleLedger;
use Uhifadhi\Seam\Service\AreaModuleService;
use Uhifadhi\Seam\Service\ModuleCatalogue;
use Uhifadhi\Seam\Service\ModuleEntryRouteResolver;
use Uhifadhi\Seam\Service\ModulePermissionCatalogue;
use Uhifadhi\Seam\Service\ProviderCatalogueMapper;
use Uhifadhi\Seam\UhifadhiSeamBundle;

/*
 * The bundle's static service wiring.
 *
 * PHP (not YAML) on purpose: a reusable bundle must not force symfony/yaml onto
 * hosts, and FQCN references stay refactor-safe and phpstan-checked. Imported by
 * UhifadhiSeamBundle::loadExtension(), which keeps only the config-DRIVEN
 * definitions.
 *
 * Everything below is defined EXPLICITLY — no autowire(), no autoconfigure(),
 * and ids prefixed with the bundle alias — because this bundle is installed by
 * other projects via Composer, which is what Symfony calls a reusable bundle:
 *
 *   "Services should not use autowiring or autoconfiguration. Instead, all
 *    services should be defined explicitly."
 *   "If the bundle defines services, they must be prefixed with the bundle alias."
 *   — https://symfony.com/doc/current/bundles/best_practices.html
 *
 * The ids are the published surface. They are private, as a reusable bundle's
 * should be; a host that wants one aliases it, and the specification suite does
 * exactly that.
 *
 *   seam.catalogue             what modules this deployment has
 *   seam.provider_mapper       provider -> catalogue row (category coercion)
 *   seam.area_modules          per-area install state: install, uninstall, order
 *   seam.area_module_ledger    what an area has and what it does not
 *   seam.entry_routes          where a module's tile links
 *   seam.permissions           the permissions installed modules declare
 *   seam.seed_catalogue        the create-only catalogue seed command
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    /*
     * THE COLLECTING END OF THE SEAM, four times over. Each of these reads the
     * providers live, from the container, in registration order — which is what
     * makes uninstalling a bundle take its module, its route and its declared
     * permissions with it on the next request rather than on the next deploy.
     */
    $providers = tagged_iterator(UhifadhiSeamBundle::MODULE_TAG);

    /*
     * Repositories keep FQCN ids — the one place the bundle-alias prefix cannot
     * be used: ServiceRepositoryCompilerPass keys its locator by SERVICE ID over
     * findTaggedServiceIds(), while ContainerRepositoryFactory looks a repository
     * up by CLASS NAME; tagged-id lookup never sees aliases.
     *
     * @see vendor/doctrine/doctrine-bundle/src/DependencyInjection/Compiler/ServiceRepositoryCompilerPass.php
     */
    $services->set(ModuleRepository::class)
        ->args([service('doctrine')])
        ->tag('doctrine.repository_service');

    $services->set(AreaModuleRepository::class)
        ->args([service('doctrine')])
        ->tag('doctrine.repository_service');

    $services->set('seam.provider_mapper', ProviderCatalogueMapper::class)
        ->args([param('seam.default_category')]);

    $services->set('seam.catalogue', ModuleCatalogue::class)
        ->args([service(ModuleRepository::class), $providers]);

    $services->set('seam.area_modules', AreaModuleService::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service(AreaModuleRepository::class),
            service('seam.catalogue'),
        ]);

    $services->set('seam.area_module_ledger', AreaModuleLedger::class)
        ->args([service('seam.catalogue'), service(AreaModuleRepository::class)]);

    $services->set('seam.entry_routes', ModuleEntryRouteResolver::class)
        ->args([$providers]);

    $services->set('seam.permissions', ModulePermissionCatalogue::class)
        ->args([$providers]);

    // Not autoconfigured (nothing here is), so the console tag is written out.
    $services->set('seam.seed_catalogue', SeedCatalogueCommand::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service(ModuleRepository::class),
            service(AreaModuleRepository::class),
            service('seam.provider_mapper'),
            $providers,
        ])
        ->tag('console.command');
};
