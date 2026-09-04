# uhifadhi/seam-module

The **seam**: the module seam runtime every uhifadhi module registers with. A
[uhifadhi](https://github.com/uhifadhilabs) platform module.

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

**Uhifadhi is one skeleton and a set of modules.**
`uhifadhi/uhifadhi` is the project skeleton — copied once, never updated;
everything else arrives as a module, updated forever. A module **registers
with the seam** (`uhifadhi/seam-module` — this repository) and **renders in
the shell** (`uhifadhi/shell-module`); everything a deployment can do —
patrols, incidents, rosters — is a module.

## What it owns

**The seam carries; it does not show.** It owns four things and no more:

- **The catalogue** — what modules exist in this deployment. Not a list anyone
  edits: a module is in the catalogue because it is installed and its provider
  carries the `uhifadhi.module` tag.
- **Per-area install state** — which modules an area has switched on, in which
  order. Deliberately a different table from the catalogue: a deployment can
  have a module that only one of its areas wants.
- **Declared permissions** — the granular permissions modules declare, gathered
  for the host to fold into its matrix. Declared, never granted.
- **The seed command** — the command that reconciles the catalogue with what is
  installed, without ever overruling an admin.

**Zero modules is a working installation.** A fresh installation with the seam
on it boots, has an empty catalogue, and is harmless. A runtime that only
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
| 1 | A module whose provider carries the `uhifadhi.module` tag appears in the catalogue; the trait defaults (`base()` = false, live, unpinned, generic page, no permissions) hold all the way through | `Integration/Module` (the seam half) + `Integration/Catalogue/ModuleCatalogueTest` |
| 2 | Zero modules: the catalogue boots, is empty, and the seed succeeds writing nothing | `Integration/EmptyCatalogueTest` + `Integration/Catalogue/ModuleCatalogueTest` |
| 3 | Per-area install/uninstall flips state for that area alone; `base()` seeds active and installable seeds parked; a pinned module can never be switched off; uninstalling removes the module's presence from the area's ledger immediately, and keeps its data | `Integration/Area/AreaModuleInstallationTest` |
| 4 | Category and status are coerced, never trusted; an unknown category falls back to **Operations** | `Unit/Catalogue/ProviderCatalogueMapperTest` |
| 5 | The seed is idempotent and **create-only** for per-area rows: a deploy never overrules an admin's on/off or ordering, and never deletes an uninstalled module's history | `Integration/Command/SeedCatalogueCommandTest` |
| 6 | Declared permissions surface through the seam, grouped by umbrella, first-declaration-wins, and vanish with the module — carrying no role and no holders | `Integration/Permission/ModulePermissionCatalogueTest` |
| 7 | Boundaries: no module named in `src/`, no host namespace, no templates, no controllers, no routes | `Unit/BoundaryTest` |
| 8 | A module's entry route is read live from its provider, never from a stored column | `Integration/Routing/ModuleEntryRouteResolverTest` |
| 9 | The catalogue tables keep their production names; the area association is resolved to a real entity | `Integration/Area/CataloguePersistenceTest` |
| 10 | Installability: the seam alone boots without an area and cannot be schema'd without one; the migration tool arrives with the bundle, and no migration versions do | `Integration/InstallabilityTest` |
| 11 | The seam answers its own area contract for nobody — it yields, which is what lets an answer-module state the resolution and an installation write no doctrine line | `Integration/InstallabilityTest` |

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
| The area contract, which a module answers | `src/Entity/AreaInterface.php` |
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

### An area is required, and a module answers it

The seam owns two tables and the per-area one has a `NOT NULL` foreign key to an
area, so until `AreaInterface` resolves to a class there is no schema to create —
every tool that walks the association stops:

```console
$ bin/console doctrine:schema:create
In MappingException.php line 72:
  Class 'Uhifadhi\Seam\Entity\AreaInterface' does not exist
```

Booting is fine — an installation between `composer require` and its first entity
must still boot, and `Integration/InstallabilityTest` pins both halves of that.

**Whoever knows the answer states the resolution.** That is the fleet's rule and
it settles this one: the seam cannot name an area class, because it holds the
per-area table for installations whose area model is their own — but the module
that *provides* an area can, and does.

```bash
composer require uhifadhi/area-module
```

[`uhifadhi/area-module`](https://github.com/uhifadhilabs/area-module) maps its own
entity and prepends the resolution, exactly the way
[`uhifadhi/team-module`](https://github.com/uhifadhilabs/team-module) answers the
user contract. **Your installation writes no `doctrine.yaml` line at all** — with
both answer-modules installed, a bare installation reaches
`doctrine:migrations:diff` with zero doctrine edits.

**This used to be a hand-step**, and it was the fleet's oldest: write a
placeholder class yourself, then uncomment a block in `config/packages/seam.yaml`.
A hand-step is for a decision only the installation can make, and "what is an
area" was only a decision because nothing shipped one. Forgetting either half
failed a long way from its cause — the container compiled, the kernel booted, and
the diff stopped on the message above with nothing pointing back at the paragraph
that was missed.

#### Bringing your own area

You write a resolution line only to **disagree**. An installation whose areas are
its own entity — its own columns, its own name for the thing — names that class
in its own config and wins, because prepended configuration loses to the
application's by Symfony's own design:

```yaml
# config/packages/doctrine.yaml (your application)
doctrine:
    orm:
        resolve_target_entities:
            Uhifadhi\Seam\Entity\AreaInterface: App\Entity\ManagementUnit
```

Merge it into the `doctrine:` block already in that file, under the existing
`orm:`, beside `mappings` — a second `doctrine:` key in one file is not valid
YAML. Your class needs `getId()` and nothing else, and it has to be in the
mapping chain: the stock doctrine-bundle recipe writes an `App\Entity` prefix, so
an entity in `src/Entity/` is already covered. If it is not, the line resolves and
the class it names is still missing from the chain:

```console
The class 'App\Entity\ManagementUnit' was not found in the chain
configured namespaces App\Entity, Uhifadhi\Seam\Entity
```

`Uhifadhi\` on its own is the platform's, not an application's — this bundle is
`Uhifadhi\Seam\` — so do not reach for it as your own root. The left-hand side is
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
installing a module is the declaration, and a second place to enable one is
a second place for the two to disagree.

## Development

```bash
composer install
composer check   # cs:check -> phpstan (max) -> the suite
```

- PHP 8.4+, PHPStan level **max**, php-cs-fixer `@Symfony` + `@Symfony:risky`.
- **Tests first, always.** A behaviour change starts as a failing test naming
  the class or service id it wants; the change is the commit that makes it
  pass. CI gates on `composer check`: one suite, one verdict, and a failure
  there is a failure.
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