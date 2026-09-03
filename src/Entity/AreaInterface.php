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

namespace UhifadhiLabs\Trunk\Entity;

/**
 * THE AREA SEAM — the one thing the trunk needs from its host, published as an
 * interface so it never has to require one.
 *
 * The trunk owns the record of which modules an area has switched on, which
 * means its {@see AreaModule} row points at a class
 * the trunk does not define. Areas belong to the host application; the trunk
 * maps the association to this interface and the host resolves it to its own
 * entity:
 *
 *     doctrine:
 *         orm:
 *             resolve_target_entities:
 *                 UhifadhiLabs\Trunk\Entity\AreaInterface: App\Entity\AreaOfInterest
 *
 * A bare id or uuid column was the alternative and was rejected: it would make
 * every "the modules of this area" query a join written by hand, and it would
 * let a row point at an area that no longer exists — exactly the orphan the
 * ON DELETE CASCADE on this association prevents.
 *
 * It asks for nothing but identity. An area is whatever the host says it is;
 * the trunk only ever needs to tell two of them apart.
 *
 * IT LIVES IN Entity/ because that is what it is: the stand-in Doctrine maps
 * the association to, resolved to a real entity at compile time. The layout
 * here is flat Symfony type-folders — Entity, Repository, Service, Enum,
 * Command — and never a folder named after a domain word.
 */
interface AreaInterface
{
    public function getId(): ?int;
}
