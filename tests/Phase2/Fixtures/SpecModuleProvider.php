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

namespace UhifadhiLabs\Trunk\Tests\Phase2\Fixtures;

use UhifadhiLabs\ModuleContracts\ModulePermission;
use UhifadhiLabs\ModuleContracts\ModuleProviderInterface;
use UhifadhiLabs\ModuleContracts\ModuleProviderTrait;

/**
 * A module, dialled to whatever a given specification needs. Fictional on
 * purpose — the trunk knows no real module by name, and neither does its suite.
 *
 * @phpstan-type Overrides array{
 *     name?: string, category?: string, status?: string, source?: ?string,
 *     pinned?: bool, core?: bool, position?: int, icon?: ?string,
 *     entryRoute?: ?string, permissions?: list<ModulePermission>,
 * }
 */
final class SpecModuleProvider implements ModuleProviderInterface
{
    use ModuleProviderTrait;

    /**
     * @param array<string, mixed> $overrides
     */
    public function __construct(
        private readonly string $slug,
        private readonly array $overrides = [],
    ) {
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function name(): string
    {
        return \is_string($this->overrides['name'] ?? null)
            ? $this->overrides['name']
            : ucfirst($this->slug);
    }

    public function category(): string
    {
        return \is_string($this->overrides['category'] ?? null) ? $this->overrides['category'] : 'operations';
    }

    public function status(): string
    {
        return \is_string($this->overrides['status'] ?? null) ? $this->overrides['status'] : 'live';
    }

    public function dataSource(): ?string
    {
        $source = $this->overrides['source'] ?? null;

        return \is_string($source) ? $source : null;
    }

    public function pinned(): bool
    {
        return true === ($this->overrides['pinned'] ?? false);
    }

    public function core(): bool
    {
        return true === ($this->overrides['core'] ?? false);
    }

    public function position(): int
    {
        return \is_int($this->overrides['position'] ?? null) ? $this->overrides['position'] : 0;
    }

    public function icon(): ?string
    {
        $icon = $this->overrides['icon'] ?? null;

        return \is_string($icon) ? $icon : null;
    }

    public function entryRoute(): ?string
    {
        $route = $this->overrides['entryRoute'] ?? null;

        return \is_string($route) ? $route : null;
    }

    /**
     * @return list<ModulePermission>
     */
    public function permissions(): array
    {
        $permissions = $this->overrides['permissions'] ?? [];

        return \is_array($permissions) ? array_values($permissions) : [];
    }
}
