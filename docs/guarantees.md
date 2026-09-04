# What it guarantees

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

## The attention-list promise

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
