# uhifadhi/seam-module

The **seam**: the module seam runtime every uhifadhi module registers with. A
[uhifadhi](https://github.com/uhifadhilabs) platform module.

## What it is

**Uhifadhi is one skeleton and a set of modules.**
`uhifadhi/uhifadhi` is the project skeleton — copied once, never updated;
everything else arrives as a module, updated forever. A module **registers
with the seam** (`uhifadhi/seam-module` — this repository) and **renders in
the shell** (`uhifadhi/shell-module`); everything a deployment can do —
patrols, incidents, rosters — is a module.

The seam carries the catalogue, the per-area record of what is switched on, the
permissions modules declare, and the seed command that keeps the catalogue in
step with what is installed. It renders nothing.

## Installation

```bash
composer require uhifadhi/seam-module
```

The bundle registers via Flex (`"type": "symfony-bundle"`), which adds
`Uhifadhi\Seam\UhifadhiSeamBundle` to `config/bundles.php` and copies
`config/packages/seam.yaml` in.

### An area is required, and a module answers it

The per-area table has a `NOT NULL` foreign key to an area, so until
`AreaInterface` resolves to a class there is no schema to create. Installing the
answer-module states the resolution for you:

```bash
composer require uhifadhi/area-module
```

**Your installation writes no `doctrine.yaml` line at all.** You write a
resolution line only to disagree — see
[docs/configuration.md](docs/configuration.md) for that, and for why the seam
cannot name an area class itself.

### Then the tables

```bash
bin/console doctrine:database:create
bin/console doctrine:migrations:diff      # your history, your migration
bin/console doctrine:migrations:migrate
bin/console seam:catalogue:seed
```

The seam ships no migration versions: the tables are the seam's, the migration
history is the installation's.

## Configuration

```yaml
# config/packages/seam.yaml (your application)
seam:
    default_category: operations   # where an unplaced module is filed
    dev_tools: false               # dev-only tooling; enable via when@dev / when@test
```

Both keys have defaults and the tree is closed. There is deliberately no key
listing modules — see [docs/configuration.md](docs/configuration.md).

## Learn more

- [docs/architecture.md](docs/architecture.md) — what the seam owns, why zero
  modules is a working installation, and where each piece lives in `src/`.
- [docs/boundaries.md](docs/boundaries.md) — what the seam is not: why the module
  grid and the customize screen belong to the shell, with the split in a table.
- [docs/guarantees.md](docs/guarantees.md) — the behaviour table, every row a
  test, and the attention-list promise behind it.
- [docs/configuration.md](docs/configuration.md) — the `seam:` tree, resolving
  the area contract, bringing your own area, and whose migration history the
  tables are.
- [docs/development.md](docs/development.md) — the standard, tests-first, and
  the two test kernels.

## License

**AGPL-3.0-or-later** — see [LICENSE](LICENSE): the same license as the uhifadhi
host this bundle carries. Use, modify and self-host freely; if you offer a
modified version to users over a network, they are entitled to the source of
what they're running.
