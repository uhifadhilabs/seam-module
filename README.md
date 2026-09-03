# uhifadhi/seam-module

The **seam**: the module seam runtime every uhifadhi module registers with. A
[uhifadhi](https://github.com/uhifadhilabs) platform bundle.

> **> [uhifadhi host](https://github.com/uhifadhilabs/uhifadhi-host) and into this
> bundle, against a specification that was written first and failing. See
> [How this was built](#how-this-was-built).

## Contents

- [The architecture](#the-architecture)
- [What it owns](#what-it-owns)
- [What it guarantees](#what-it-guarantees)
- [Boundaries: what the seam is not](#boundaries-what-the-seam-is-not)
- [What is here](#what-is-here)
- [Installation](#installation)
- [Configuration](#configuration)
- [Development](#development)
- [License](#license)

## The architecture

**Uhifadhi is one skeleton and a set of bundles.**
`uhifadhi/uhifadhi` is the project skeleton — copied once, never updated;
everything else arrives as a bundle, updated forever. A module **registers
with the seam** (`uhifadhi/seam-module` — this repository) and **renders in
the shell** (`uhifadhi/shell-module`); everything a deployment can do —
patrols, incidents, rosters — is a module.

## What it owns

**The seam carries; it does not show.** It owns four things and no more:

- **The catalogue** — what modules exist in this deployment. Not a list anyone
  edits: a module is in the catalogue because its bundle is installed and its
  provider carries the `uhifadhi.module` tag.
- **Per-area install state** — which modules an area has switched on, in which
  order. Deliberately a different table from the catalogue: a deployment can
  have a module that only one of its areas wants.
- **Declared permissions** — the granular permissions modules declare, gathered
  for the host to fold into its matrix. Declared, never granted.
- **The seed command** — the command that reconciles the catalogue with what is
  installed, without ever overruling an admin.

**Zero modules is a working installation.** A fresh installation with this
bundle on it boots, has an empty catalogue, and is harmless. A runtime that only
functions once somebody installs a module has a hidden dependency on its own
modules.

**It knows no module by name.** Not one — not even the pinned hub every
installation has. A module is whatever tagged itself, and everything the seam
treats specially (`pinned`, `base`) is a flag the provider declares, never a
slug the runtime recognises. A test sweeps `src/` for that property.

## What it guarantees

Every row below is a test, and the table is the order they were written in:

| # | Behaviour | Where |
|---|---|---|
| 1 | A bundle tagging a provider `uhifadhi.module` appears in the catalogue; the trait defaults (`base()` = false, live, unpinned, generic page, no permissions) hold all the way through | `Integration/Module` (the seam half) + `Integration/Catalogue/ModuleCatalogueTest` |
| 2 | Zero modules: the catalogue boots, is empty, and the seed succeeds writing nothing | `Integration/EmptyCatalogueTest` + `Integration/Catalogue/ModuleCatalogueTest` |
| 3 | Per-area install/uninstall flips state for that area alone; `base()` seeds active and installable seeds parked; a pinned module can never be switched off; uninstalling removes the module's presence from the area's ledger immediately, and keeps its data | `Integration/Area/AreaModuleInstallationTest` |
| 4 | Category and status are coerced, never trusted; an unknown category falls back to **Operations** | `Unit/Catalogue/ProviderCatalogueMapperTest` |
| 5 | The seed is idempotent and **create-only** for per-area rows: a deploy never overrules an admin's on/off or ordering, and never deletes an uninstalled module's history | `Integration/Command/SeedCatalogueCommandTest` |
| 6 | Declared permissions surface through the seam, grouped by umbrella, first-declaration-wins, and vanish with the module — carrying no role and no holders | `Integration/Permission/ModulePermissionCatalogueTest` |
| 7 | Boundaries: no module named in `src/`, no host namespace, no templates, no controllers, no routes | `Unit/BoundaryTest` |
| 8 | A module's entry route is read live from its provider, never from a stored column | `Integration/Routing/ModuleEntryRouteResolverTest` |
| 9 | The catalogue tables keep their production names; the area association is resolved to the host's own entity | `Integration/Area/CataloguePersistenceTest` |
| 10 | Installability: the seam alone boots without an area mapping and cannot be schema'd without one; the migration tool arrives with the bundle, and no migration versions do | `Integration/InstallabilityTest` |

### The attention-list promise

Every contribution a module makes to a shared surface — an attention item, a
now-tile, a map layer, a KPI — is keyed by its module slug, and the promise the
platform makes to an area manager is that switching a module off takes its
contributions off the page **the same day**, not on the next deploy or after a
cache warm.

The seam cannot test the attention list itself: it draws nothing and knows no
module. What it can pin is what that list is derived from — the moment a module
is uninstalled for an area, that area's ledger stops counting it present and
starts counting it absent, read from the database, with no interval in between.
Anything that caches that reading breaks the promise, and the test is where it
would show.

## Boundaries: what the seam is not

**The module grid is not here, and neither is the customize screen.** This was
the phase-1 decision worth arguing, so here is the argument.

The test is independent life: can this bundle live alone and still be useful? The
runtime can — a catalogue, a per-area install record, a permission collector and
a seed command are complete and meaningful with nothing rendering them, and a
CLI or an API can use every one of them. A module grid cannot live alone: it is
a *picture* of those answers, and it needs a layout, a stylesheet, a department
lens over the ordering, and the viewer's identity — none of which the seam has
or should acquire.

So the split is:

| Belongs to the seam | Belongs to the shell / host |
|---|---|
| the catalogue, in catalogue order | the module grid, its cards, its category pills |
| per-area active/parked state and ordering | the customize screen and its forms |
| the ledger: what an area has and has not | the "modules in this area" and "not installed here" widgets |
| a module's entry route, resolved | the link built from it, with the area's uuid |
| the permissions modules declare | the permission matrix that assigns them |

The host's `ModuleGridService` is the case that looks borderline: it returns
arrays, not HTML. It stays out, because what it actually does is group cards by
category **and by the viewer's department**, which is a reading for a person on
a page — a view-model, and the seam has no viewer.

Concretely: this bundle ships **no `templates/` directory, no controllers and no
routes**, and `Unit/BoundaryTest` fails the build if that changes.

## What is here

| Piece | File |
|---|---|
| The Symfony plug, and the `uhifadhi.module` tag | `src/UhifadhiSeamBundle.php` |
| Config tree (`seam:`) | `src/DependencyInjection/SeamConfiguration.php` |
| Static service wiring, and the published ids | `config/services.php` |
| The area seam a host resolves | `src/Entity/AreaInterface.php` |
| The catalogue and the per-area ledger | `src/Entity/`, `src/Repository/` |
| The runtime | `src/Service/` |
| The create-only seed | `src/Command/SeedCatalogueCommand.php` |
| Test host app | `tests/Integration/TestKernel.php` |
| A host, minimally: area entity + resolved target entity | `tests/Integration/Fixtures/HostKernel.php` |

The bundle maps its own entity directory, so a host never needs to write a
doctrine mappings block for the catalogue tables.

## Installation

```bash
composer require uhifadhi/seam-module
```

The bundle registers via Flex (`"type": "symfony-bundle"`), which adds
`Uhifadhi\Seam\UhifadhiSeamBundle` to `config/bundles.php` and copies
`config/packages/seam.yaml` in.

### The area mapping is required, not optional

This used to read as an extra you added when you wanted the per-area half. It
is not. The seam owns two tables and the per-area one has a `NOT NULL` foreign
key to an area, so until the interface resolves to a class there is no schema
to create — every tool that walks the association stops:

```console
$ bin/console doctrine:schema:create
In MappingException.php line 72:
  Class 'Uhifadhi\Seam\Entity\AreaInterface' does not exist
```

Booting is fine — a host between `composer require` and its first entity must
still boot, and `Integration/InstallabilityTest` pins both halves of that.

So give the application an area entity implementing `AreaInterface` (it asks
for `getId()` and nothing else):

```php
// src/Entity/AreaOfInterest.php (your application)
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Uhifadhi\Seam\Entity\AreaInterface;

#[ORM\Entity]
class AreaOfInterest implements AreaInterface
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
```

and name it:

```yaml
# config/packages/seam.yaml (your application)
doctrine:
    orm:
        resolve_target_entities:
            Uhifadhi\Seam\Entity\AreaInterface: App\Entity\AreaOfInterest
```

`App\Entity` is the PSR-4 root of a project created from the skeleton
([`uhifadhi/uhifadhi`](https://github.com/uhifadhilabs/uhifadhi)) — the skeleton
is stock Symfony, so the mapping prefix the doctrine-bundle recipe writes into
`config/packages/doctrine.yaml` already covers the area entity and needs no
correction. `Uhifadhi\` on its own is the platform's, not an application's — this
bundle is `Uhifadhi\Seam\` — so do not reach for it here. An existing host with
a root of its own substitutes that on the right-hand side; the left-hand side is
the seam's and never changes.

### Then the tables

```bash
bin/console doctrine:database:create
bin/console doctrine:migrations:diff      # your history, your migration
bin/console doctrine:migrations:migrate
bin/console seam:catalogue:seed
```

`doctrine/doctrine-migrations-bundle` is a dependency of **this** bundle: the
bundle that adds tables brings the tool that creates them, the same way it has
always brought the ORM. An installed project that lacked it had no
`doctrine:migrations:*` commands at all and no hint that it should.

What the seam deliberately does **not** ship is migration versions. The tables
are the seam's; the migration history is the installation's, and a vendor
replaying its own versions into it would fight every `diff` the host ever runs.

## Configuration

```yaml
# config/packages/seam.yaml (your application)
seam:
    default_category: operations   # where an unplaced module is filed
    dev_tools: false               # dev-only tooling; enable via when@dev / when@test
```

Both keys have defaults; the tree is closed, so an unknown key fails loudly
rather than being ignored. There is deliberately **no key listing modules** —
installing a bundle is the declaration, and a second place to enable a module is
a second place for the two to disagree.

## Development

```bash
composer install
composer check   # cs:check -> phpstan (max) -> the suite
```

- PHP 8.4+, PHPStan level **max**, php-cs-fixer `@Symfony` + `@Symfony:risky`.
- **Tests first, always.** This repository is that rule taken literally: the
  whole specification was written before a line of the runtime existed.
- `tests/Integration/TestKernel.php` is the seam alone — framework, doctrine,
  this bundle — and opens no connection, which is what a host that has not
  migrated yet must still be able to boot.
  `tests/Integration/Fixtures/HostKernel.php` adds a stand-in host on top and
  does connect, because the seam genuinely owns tables:
  `postgresql://app:app@127.0.0.1:5434/seam_bundle_test` on the fundi cluster.

## License

**AGPL-3.0-or-later** — see [LICENSE](LICENSE): the same license as the uhifadhi
host this bundle carries. Use, modify and self-host freely; if you offer a
modified version to users over a network, they are entitled to the source of
what they're running.