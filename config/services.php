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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

/*
 * The bundle's static service wiring.
 *
 * PHP (not YAML) on purpose: a reusable bundle must not force symfony/yaml onto
 * hosts, and FQCN references stay refactor-safe and phpstan-checked. Imported by
 * UhifadhiLabsTrunkBundle::loadExtension(), which keeps only the config-DRIVEN
 * definitions.
 *
 * Everything defined here is defined EXPLICITLY — no autowire(), no autoconfigure(),
 * and ids prefixed with the bundle alias — because this bundle is installed by other
 * projects via Composer, which is what Symfony calls a reusable bundle:
 *
 *   "Services should not use autowiring or autoconfiguration. Instead, all
 *    services should be defined explicitly."
 *   "If the bundle defines services, they must be prefixed with the bundle alias."
 *   — https://symfony.com/doc/current/bundles/best_practices.html
 *
 * Empty in phase 1, and on purpose. The runtime arrives by EXTRACTION from the
 * host in phase 2, against the failing specification in tests/Phase2; that
 * specification names the service ids it will land under, which is the whole
 * contract this file has to satisfy:
 *
 *   trunk.catalogue             the module catalogue read model
 *   trunk.provider_mapper       provider -> catalogue row (category coercion)
 *   trunk.area_modules          per-area install state: install, uninstall, order
 *   trunk.area_module_ledger    what an area has and what it does not
 *   trunk.entry_routes          where a module's tile links
 *   trunk.permissions           the permissions installed modules declare
 *   trunk.seed_catalogue        the create-only catalogue seed command
 *
 * The file exists so the first of them lands in the right place, in the right
 * style, rather than being autowired into the host's habits.
 */
return static function (ContainerConfigurator $container): void {
    $container->services();
};
