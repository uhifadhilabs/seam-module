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

namespace Uhifadhi\Seam\Entity;

/**
 * THE AREA SEAM — the one thing the seam needs from its host, published as an
 * interface so it never has to require one.
 *
 * The seam owns the record of which modules an area has switched on, which
 * means its {@see AreaModule} row points at a class
 * the seam does not define. Areas belong to the host application; the seam
 * maps the association to this interface and the host resolves it to its own
 * entity:
 *
 *     doctrine:
 *         orm:
 *             resolve_target_entities:
 *                 Uhifadhi\Seam\Entity\AreaInterface: <YourRoot>\Entity\AreaOfInterest
 *
 * The placeholder is deliberate: Unit\BoundaryTest sweeps this directory for the
 * host tree under either root an application may carry, so no example here may
 * spell one out. A project installed from the uhifadhi skeleton is stock Symfony — its
 * root is the ordinary one every Symfony application ships with — so the
 * doctrine-bundle recipe's own mapping prefix already covers the area entity,
 * and the line above is the only wiring the seam asks for. The README and the
 * recipe name the concrete class.
 *
 * REQUIRED, NOT OPTIONAL. The bundle boots without it — a host that has not
 * written its area entity yet must still boot — but nothing can build a schema
 * without it: the association below is NOT NULL, so every metadata walk stops
 * with "Class 'Uhifadhi\Seam\Entity\AreaInterface' does not exist". See
 * Integration/InstallabilityTest.
 *
 * A bare id or uuid column was the alternative and was rejected: it would make
 * every "the modules of this area" query a join written by hand, and it would
 * let a row point at an area that no longer exists — exactly the orphan the
 * ON DELETE CASCADE on this association prevents.
 *
 * It asks for nothing but identity. An area is whatever the host says it is;
 * the seam only ever needs to tell two of them apart.
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
