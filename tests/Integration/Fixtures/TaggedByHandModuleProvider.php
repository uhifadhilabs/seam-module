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

namespace Uhifadhi\Trunk\Tests\Integration\Fixtures;

use Uhifadhi\ModuleContracts\ModuleProviderInterface;
use Uhifadhi\ModuleContracts\ModuleProviderTrait;

/**
 * A MODULE BUNDLE'S provider: not autoconfigured (a reusable bundle's services
 * never are), so its kernel writes the tag by hand — the other of the seam's
 * two entrances, and the one every installable module actually uses.
 */
final class TaggedByHandModuleProvider implements ModuleProviderInterface
{
    use ModuleProviderTrait;

    public function slug(): string
    {
        return 'ferries';
    }

    public function name(): string
    {
        return 'Ferries';
    }

    public function category(): string
    {
        return 'operations';
    }

    public function core(): bool
    {
        return true;
    }
}
