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
 * THE AREA SEAM — the one thing the seam needs from outside itself, published as
 * an interface so it never has to require a particular answer.
 *
 * The seam owns the record of which modules an area has switched on, which means
 * its {@see AreaModule} row points at a class the seam does not define. It
 * cannot define one: it holds that table for installations whose area model is
 * their own. So it maps the association to this interface, and something else
 * resolves it.
 *
 * WHOEVER KNOWS THE ANSWER STATES THE RESOLUTION. That is the fleet's rule, and
 * for this contract the answer-module is uhifadhi/area-module: it provides an
 * area entity, so it is the package that prepends
 *
 *     doctrine:
 *         orm:
 *             resolve_target_entities:
 *                 Uhifadhi\Seam\Entity\AreaInterface: <the answering module's entity>
 *
 * and an installation writes nothing. An installation writes that line only to
 * DISAGREE — naming its own class, which wins, because prepended configuration
 * loses to the application's. The right-hand side is deliberately not spelled
 * out here: Unit\BoundaryTest sweeps this directory for an application tree
 * under either root one may carry, so no example may name a concrete class. The
 * docs/configuration.md and the recipe do.
 *
 * IT USED TO BE A HAND-STEP — a placeholder class the installation wrote and a
 * block it uncommented — and that was the wrong shape. A hand-step is for a
 * decision only the installation can make, and "what is an area" was only a
 * decision because nothing shipped one.
 *
 * REQUIRED, NOT OPTIONAL. The bundle boots without an answer — an installation
 * between `composer require` and its first module must still boot — but nothing
 * can build a schema without one: the association below is NOT NULL, so every
 * metadata walk stops with "Class 'Uhifadhi\Seam\Entity\AreaInterface' does not
 * exist". See Integration/InstallabilityTest.
 *
 * A bare id or uuid column was the alternative and was rejected: it would make
 * every "the modules of this area" query a join written by hand, and it would
 * let a row point at an area that no longer exists — exactly the orphan the
 * ON DELETE CASCADE on this association prevents.
 *
 * It asks for nothing but identity. An area is whatever the installation ends up
 * with; the seam only ever needs to tell two of them apart.
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
