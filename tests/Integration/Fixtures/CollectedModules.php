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

use UhifadhiLabs\ModuleContracts\ModuleProviderInterface;

/**
 * Everything that reached the module tag, in registration order — the trunk's
 * own catalogue receives exactly this iterator, and until the extraction lands
 * this fixture is the only thing that can observe it.
 */
final readonly class CollectedModules
{
    /**
     * @param iterable<ModuleProviderInterface> $providers
     */
    public function __construct(
        private iterable $providers,
    ) {
    }

    /**
     * @return list<ModuleProviderInterface>
     */
    public function all(): array
    {
        $all = [];
        foreach ($this->providers as $provider) {
            $all[] = $provider;
        }

        return $all;
    }

    /**
     * @return array<string, ModuleProviderInterface> keyed by slug
     */
    public function bySlug(): array
    {
        $bySlug = [];
        foreach ($this->providers as $provider) {
            $bySlug[$provider->slug()] = $provider;
        }

        return $bySlug;
    }
}
