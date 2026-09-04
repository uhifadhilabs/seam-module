# Boundaries: what the seam is not

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
