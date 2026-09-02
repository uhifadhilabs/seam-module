# uhifadhi/trunk-module

The **trunk**: the module seam runtime every uhifadhi module registers with. A
[uhifadhi](https://github.com/uhifadhilabs) platform bundle.

> **Status: scaffold + specification.** This repository contains the bundle,
> its configuration seam, the module tag — and a deliberately **failing** test
> suite describing the runtime that has not been extracted yet. See
> [Phase 1 and phase 2](#phase-1-and-phase-2).

## Contents

- [The tree](#the-tree)
- [Charter](#charter)
- [Phase 1 and phase 2](#phase-1-and-phase-2)
- [The specification](#the-specification)
- [Boundaries: what the trunk is not](#boundaries-what-the-trunk-is-not)
- [Deltas from the host's behaviour today](#deltas-from-the-hosts-behaviour-today)
- [What is here](#what-is-here)
- [Installation](#installation)
- [Configuration](#configuration)
- [Development](#development)
- [License](#license)

## The tree

Uhifadhi is structured like the thing it protects:

> **`uhifadhi/seed`** (planted once) → **`trunk-module`** (this repository: the
> seam runtime every module registers with) → **branches** (the modules) →
> **`canopy-module`** (the visible crown).

The seed is copied once and is then yours forever. Everything above it is a
bundle, updated through composer. This ring is the one every other ring
attaches to.

## Charter

**The trunk carries; it does not show.** It owns four things and no more:

- **The catalogue** — what modules exist in this deployment. Not a list anyone
  edits: a module is in the catalogue because its bundle is installed and its
  provider carries the `uhifadhi.module` tag.
- **Per-area install state** — which modules an area has switched on, in which
  order. Deliberately a different table from the catalogue: a deployment can
  have a module that only one of its areas wants.
- **Declared permissions** — the granular permissions modules declare, gathered
  for the host to fold into its matrix. Declared, never granted.
- **The seed** — the command that reconciles the catalogue with what is
  installed, without ever overruling an admin.

**Zero modules is a working installation.** A freshly planted seed with this
bundle on it boots, has an empty catalogue, and is harmless. A runtime that only
functions once somebody installs a module has a hidden dependency on its own
branches.

**It knows no module by name.** Not one — not even the pinned hub every
installation has. A module is whatever tagged itself, and everything the trunk
treats specially (`pinned`, `core`) is a flag the provider declares, never a
slug the runtime recognises. A test sweeps `src/` for that property.

## Phase 1 and phase 2

The module seam exists today, working, inside the
[uhifadhi](https://github.com/uhifadhilabs/uhifadhi) host application. This
repository extracts it — and because this project is test-first, the
specification is written before the move rather than after it.

| | Phase 1 (this commit) | Phase 2 |
|---|---|---|
| `src/` | the bundle, its config tree, the module tag | the extracted runtime |
| `tests/Unit`, `tests/Integration` | **green**, and stays green | joined by the specification below |
| `tests/Phase2` | **red by design** — 47 tests, 47 errors | green, then deleted as a directory |

The two suites are separate, not skipped, because "red by design" and "broken"
must never be confusable:

```bash
composer check        # cs -> phpstan max -> the SCAFFOLD suite. Green. CI gates on this.
composer test:phase2  # the specification. Red until the extraction. CI reports, does not gate.
```

`tests/Phase2` is excluded from PHPStan for the same reason it is excluded from
`composer test`: it names classes that do not exist yet, and static analysis
would report their absence as an error rather than as the point. php-cs-fixer
still covers it — style is checkable without the classes. **When the extraction
lands, the exclusion in `phpstan.dist.neon`, the second suite in
`phpunit.dist.xml` and the `continue-on-error` step in CI are all deleted in the
commit that moves the files.** If any of them is still there, the extraction is
not finished.

## The specification

What phase 2 has to satisfy, in the order the tests are written:

| # | Behaviour | Where |
|---|---|---|
| 1 | A bundle tagging a provider `uhifadhi.module` appears in the catalogue; the trait defaults (`core()` = false, live, unpinned, generic page, no permissions) hold all the way through | `Integration/Module` (seam half, **green**) + `Phase2/Catalogue/ModuleCatalogueTest` |
| 2 | Zero modules: the catalogue boots, is empty, and the seed succeeds writing nothing | `Integration/EmptyCatalogueTest` (**green**) + `Phase2/Catalogue/ModuleCatalogueTest` |
| 3 | Per-area install/uninstall flips state for that area alone; `core()` seeds active and installable seeds parked; a pinned module can never be switched off; uninstalling removes the module's presence from the area's ledger immediately, and keeps its data | `Phase2/Area/AreaModuleInstallationTest` |
| 4 | Category and status are coerced, never trusted; an unknown category falls back to **Operations** | `Phase2/Catalogue/ProviderCatalogueMapperTest` |
| 5 | The seed is idempotent and **create-only** for per-area rows: a deploy never overrules an admin's on/off or ordering, and never deletes an uninstalled module's history | `Phase2/Command/SeedCatalogueCommandTest` |
| 6 | Declared permissions surface through the seam, grouped by umbrella, first-declaration-wins, and vanish with the module — carrying no role and no holders | `Phase2/Permission/ModulePermissionCatalogueTest` |
| 7 | Boundaries: no module named in `src/`, no host namespace, no templates, no controllers, no routes | `Unit/BoundaryTest` (**green**) |
| 8 | A module's entry route is read live from its provider, never from a stored column | `Phase2/Routing/ModuleEntryRouteResolverTest` |
| 9 | The catalogue tables keep their production names; the area association is resolved to the host's own entity | `Phase2/Area/CataloguePersistenceTest` |

### The attention-list promise (spec 3)

Every contribution a module makes to a shared surface — an attention item, a
now-tile, a map layer, a KPI — is keyed by its module slug, and the promise the
platform makes to an area manager is that switching a module off takes its
contributions off the page **the same day**, not on the next deploy or after a
cache warm.

The trunk cannot test the attention list itself: it draws nothing and knows no
module. What it can pin is what that list is derived from — the moment a module
is uninstalled for an area, that area's ledger stops counting it present and
starts counting it absent, read from the database, with no interval in between.
Anything that caches that reading breaks the promise, and the test is where it
would show.

## Boundaries: what the trunk is not

**The module grid is not here, and neither is the customize screen.** This was
the phase-1 decision worth arguing, so here is the argument.

The test is independent life: can this ring live alone and still be useful? The
runtime can — a catalogue, a per-area install record, a permission collector and
a seed command are complete and meaningful with nothing rendering them, and a
CLI or an API can use every one of them. A module grid cannot live alone: it is
a *picture* of those answers, and it needs a layout, a stylesheet, a department
lens over the ordering, and the viewer's identity — none of which the trunk has
or should acquire.

So the split is:

| Belongs to the trunk | Belongs to the canopy / host |
|---|---|
| the catalogue, in catalogue order | the module grid, its cards, its category pills |
| per-area active/parked state and ordering | the customize screen and its forms |
| the ledger: what an area has and has not | the "modules in this area" and "not installed here" widgets |
| a module's entry route, resolved | the link built from it, with the area's uuid |
| the permissions modules declare | the permission matrix that assigns them |

The host's `ModuleGridService` is the case that looks borderline: it returns
arrays, not HTML. It stays out, because what it actually does is group cards by
category **and by the viewer's department**, which is a reading for a person on
a page — a view-model, and the trunk has no viewer.

Concretely: this bundle ships **no `templates/` directory, no controllers and no
routes**, and `Unit/BoundaryTest` fails the build if that changes.

## Deltas from the host's behaviour today

Where the host's current behaviour is accidental or undocumented, the
specification pins the honest version rather than the incumbent one. Each of
these is a deliberate change the extraction must make:

1. **`position()` is honoured.** `ModuleProviderInterface::position()` is
   documented as an ordering hint, and today nothing reads it — the seed passes
   its own loop index and the provider's answer is discarded. A contract method
   nothing reads is a lie in the contract, and an author who sets it has no way
   to find out it did nothing. The trunk honours a declared position and uses
   registration order only as the tie-break (which, given the trait default of
   `0`, leaves the common case unchanged).
2. **There is no hub row.** The host's seed command still says in its docblock
   that the catalogue is "the host's own Overview hub plus every provider"; the
   code has not created a hub row since the catalogue became provider-driven.
   The trunk states the honest rule: every row comes from a provider, and
   `pinned` is a flag a provider declares — the runtime never knows the hub's
   slug.
3. **The command is namespaced.** `trunk:catalogue:seed`, because a bundle's
   command belongs in the bundle's namespace — with `app:seed:catalogue` kept as
   an alias, because that string is in the deploy pipeline and in every module's
   README, and renaming it silently would break a deploy rather than a test.
4. **The tables are deliberately unprefixed.** Every branch prefixes its tables
   with its domain word; the trunk keeps `module` and `area_module`. A rename
   here is a production migration on the platform's most-referenced tables,
   bought with nothing but consistency — and the prefix rule exists to stop two
   bundles colliding on a common noun, which cannot happen to the runtime every
   bundle registers with.
5. **Permissions are the module half only.** The host's permission catalogue
   merges its core enum (Areas, Ingestion, Modules, Team) with module
   declarations. The trunk collects only the declarations; the host keeps its
   own enum and stays the only thing that decides who holds what. A runtime that
   also owned the core permissions would own the host's team model with it.
6. **A declaration is deployment-wide, not per area** — a module switched off
   everywhere still declares its permissions, so an admin can assign one that
   currently guards nothing. This is the seam's honest state today; it is pinned
   in a test rather than quietly narrowed, because narrowing it is a ruling
   about the team model.

## What is here

| Piece | File |
|---|---|
| The Symfony plug, and the `uhifadhi.module` tag | `src/UhifadhiLabsTrunkBundle.php` |
| Config tree (`trunk:`) | `src/DependencyInjection/TrunkConfiguration.php` |
| Static service wiring (empty, with the ids phase 2 must land on) | `config/services.php` |
| Test host app | `tests/Integration/TestKernel.php` |
| A host, minimally: area entity + resolved target entity | `tests/Phase2/Fixtures/HostKernel.php` |
| The specification | `tests/Phase2/` |

The bundle maps its own entity directory (`src/Entity`, empty for now), so a
host will never need to write a doctrine mappings block for the catalogue
tables.

## Installation

Not yet — the runtime lands in phase 2. For the record, the steps will be:

```bash
composer require uhifadhi/trunk-module
bin/console trunk:catalogue:seed
```

The bundle registers via Flex (`"type": "symfony-bundle"`), which adds
`UhifadhiLabs\Trunk\UhifadhiLabsTrunkBundle` to `config/bundles.php`. A host
also resolves the trunk's area interface to its own area entity:

```yaml
doctrine:
    orm:
        resolve_target_entities:
            UhifadhiLabs\Trunk\Area\AreaInterface: App\Entity\AreaOfInterest
```

## Configuration

```yaml
# config/packages/trunk.yaml
trunk:
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
composer check        # cs:check -> phpstan (max) -> the scaffold suite
composer test:phase2  # the specification: red until the extraction
```

- PHP 8.4+, PHPStan level **max**, php-cs-fixer `@Symfony` + `@Symfony:risky`.
- **Tests first, always.** This repository is that rule taken literally: the
  whole specification was written before a line of the runtime existed.
- The scaffold suite boots a real kernel (`tests/Integration/TestKernel.php`)
  and opens no database connection. The phase-2 suite does connect — the trunk
  genuinely owns tables — to `postgresql://app:app@127.0.0.1:5434/trunk_bundle_test`
  on the fundi cluster.

## License

**AGPL-3.0-or-later** — see [LICENSE](LICENSE): the same license as the uhifadhi
host this bundle carries. Use, modify and self-host freely; if you offer a
modified version to users over a network, they are entitled to the source of
what they're running.