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

namespace Uhifadhi\Entity;

use Doctrine\ORM\Mapping as ORM;
use UhifadhiLabs\Trunk\Entity\AreaInterface;

/**
 * THE HOST'S AREA, PLAYED BY A STAND-IN — and the reason the trunk needs a seam
 * here at all.
 *
 * An area belongs to the host application: the host owns areas, team and
 * nothing else (that is the lean-flat-host ruling). The trunk owns the record
 * of which modules an area has, which means its AreaModule row has to point at
 * a class the trunk does not define and must not require.
 *
 * The answer is Doctrine's own: the trunk maps the association to
 * {@see AreaInterface} and the host resolves that interface to its real entity
 * with `doctrine.orm.resolve_target_entities`. This fixture is a host, minimally
 * — a real entity in the host's namespace, implementing the trunk's interface,
 * autoloaded in this suite alone (see composer.json autoload-dev).
 *
 * The alternative — storing a bare area id or uuid on the row — was considered
 * and rejected: it would make every "the modules of this area" query a manual
 * join the trunk writes by hand, and it would let a row point at an area that
 * no longer exists, which is exactly the kind of orphan the ON DELETE CASCADE
 * on this association prevents today.
 */
#[ORM\Entity]
#[ORM\Table(name: 'area_of_interest')]
class AreaOfInterest implements AreaInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null; // @phpstan-ignore property.unusedType (assigned by Doctrine via reflection)

    #[ORM\Column(length: 120)]
    private string $name = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
