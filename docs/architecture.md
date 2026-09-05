# Architecture

What the seam owns, what a fresh installation looks like with nothing on it, and
where each piece lives in this repository.

## What it owns

**The seam carries; it does not show.** It owns five things and no more:

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
- **The route gate** — where an area has parked a module, that module's routes
  answer 404 there. One enforcement point, no per-module code, and nothing
  rendered: see `docs/guarantees.md` for how a request is recognised and
  `docs/boundaries.md` for why closing a route is not drawing one.

**Zero modules is a working installation.** A fresh installation with the seam
on it boots, has an empty catalogue, and is harmless. A runtime that only
functions once somebody installs a module has a hidden dependency on its own
modules.

**It knows no module by name.** Not one — not even the pinned hub every
installation has. A module is whatever tagged itself, and everything the seam
treats specially (`pinned`, `base`) is a flag the provider declares, never a
slug the runtime recognises. A test sweeps `src/` for that property.

## What is here

| Piece | File |
|---|---|
| The Symfony plug, and the `uhifadhi.module` tag | `src/UhifadhiSeamBundle.php` |
| Config tree (`seam:`) | `src/DependencyInjection/SeamConfiguration.php` |
| Static service wiring, and the published ids | `config/services.php` |
| The area contract, which a module answers | `src/Entity/AreaInterface.php` |
| The catalogue and the per-area ledger | `src/Entity/`, `src/Repository/` |
| The runtime | `src/Service/` |
| The route gate, applied to every request | `src/EventListener/ParkedModuleListener.php` |
| The create-only seed | `src/Command/SeedCatalogueCommand.php` |
| Test host app | `tests/Integration/TestKernel.php` |
| A host, minimally: area entity + resolved target entity | `tests/Integration/Fixtures/HostKernel.php` |

The bundle maps its own entity directory, so a host never needs to write a
doctrine mappings block for the catalogue tables.
