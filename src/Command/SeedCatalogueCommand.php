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

namespace UhifadhiLabs\Trunk\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UhifadhiLabs\ModuleContracts\ModuleProviderInterface;
use UhifadhiLabs\Trunk\Entity\AreaInterface;
use UhifadhiLabs\Trunk\Entity\AreaModule;
use UhifadhiLabs\Trunk\Entity\Module;
use UhifadhiLabs\Trunk\Repository\AreaModuleRepository;
use UhifadhiLabs\Trunk\Repository\ModuleRepository;
use UhifadhiLabs\Trunk\Service\ProviderCatalogueMapper;

/**
 * RECONCILE THE CATALOGUE WITH WHAT IS INSTALLED — the command a deploy runs.
 *
 * PROVIDER-DRIVEN, WITHOUT EXCEPTION. Every row comes from a tagged
 * {@see ModuleProviderInterface}: a module bundle declares itself and appears
 * here, and nothing else does. There is no row the runtime writes for itself and
 * no slug it recognises — `pinned` and `core` are flags a provider declares.
 *
 * CREATE-ONLY, AND THAT IS A PRODUCTION PROMISE. This runs on every deploy,
 * against a database with real areas configured by real admins, and there is no
 * undo. So the rule that matters most is what it does NOT touch:
 *
 *   the catalogue row is the MODULE'S — the provider owns its name, category,
 *   provenance and icon, and every seed refreshes them;
 *   the per-area row is the ADMIN'S — the seed creates it when it is missing and
 *   never revisits it again.
 *
 * An admin who parked a core module is not overruled by a deploy. A deploy that
 * reorders the catalogue does not reshuffle anybody's sub-nav. And an uninstalled
 * module's rows are LEFT ALONE rather than deleted: a bundle removed by mistake
 * and reinstalled next morning finds every area as it left it, and a deploy
 * command that deletes data on a missing dependency is a command nobody should
 * run on production.
 *
 * ZERO MODULES IS A SUCCESSFUL SEED. A freshly planted installation with no
 * branches on it runs this and is told, correctly, that there was nothing to do.
 */
#[AsCommand(
    name: 'trunk:catalogue:seed',
    // The old host name survives as an alias, because that string is written
    // into deploy pipelines and into every module's README: renaming it
    // silently would break a deploy rather than a test.
    aliases: ['app:seed:catalogue'],
    description: 'Reconcile the module catalogue with the installed module providers (idempotent).',
)]
final class SeedCatalogueCommand extends Command
{
    /**
     * @param iterable<ModuleProviderInterface> $providers every tagged provider, in registration order
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ModuleRepository $modules,
        private readonly AreaModuleRepository $areaModules,
        private readonly ProviderCatalogueMapper $mapper,
        private readonly iterable $providers = [],
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1) Upsert the catalogue by SLUG, which is a module's identity. A module
        //    that renames itself between releases is followed; a module that
        //    changed nothing is written back identically.
        $bySlug = [];
        $order = 0;
        foreach ($this->providers as $provider) {
            $row = $this->mapper->toRow($provider, $order++);

            $module = $this->modules->findBySlug($row['slug']) ?? new Module();
            $module->setSlug($row['slug'])
                ->setName($row['name'])
                ->setCategory($row['category'])
                ->setStatus($row['status'])
                ->setDataSource($row['source'])
                ->setIcon($row['icon'])
                ->setPinned($row['pinned'])
                ->setPosition($row['position']);

            $this->em->persist($module);
            $bySlug[$row['slug']] = [$module, $row['active']];
        }
        $this->em->flush();

        // 2) Backfill every area with the modules it has no row for at all —
        //    including the areas created between two deploys, which is the half
        //    of this command that is not create-only-shaped and the reason it
        //    exists. An area that already has a row keeps it exactly as it is.
        $backfilled = 0;
        foreach ($this->areas() as $area) {
            $have = [];
            foreach ($this->areaModules->forArea($area) as $areaModule) {
                $have[(string) $areaModule->getModule()?->getSlug()] = true;
            }

            foreach ($bySlug as $slug => [$module, $active]) {
                if (isset($have[$slug])) {
                    continue;
                }
                $this->em->persist(new AreaModule()
                    ->setArea($area)
                    ->setModule($module)
                    ->setActive($active)
                    ->setPosition($module->getPosition()));
                ++$backfilled;
            }
        }
        $this->em->flush();

        $io->success(\sprintf(
            'Catalogue reconciled: %d module(s) from %d installed provider(s); %d area assignment(s) backfilled.',
            \count($bySlug),
            \count($bySlug),
            $backfilled,
        ));

        return Command::SUCCESS;
    }

    /**
     * EVERY AREA, WITHOUT KNOWING WHAT AN AREA IS. The host resolved
     * {@see AreaInterface} to its own entity when it mapped this bundle's
     * association, so the concrete class is already recorded there — asking
     * Doctrine for it is how the trunk iterates a model it does not define.
     *
     * A host that installed the bundle without resolving the interface has an
     * unusable per-area table; there are no areas to backfill, and saying so is
     * more useful than a fatal.
     *
     * @return iterable<AreaInterface>
     */
    private function areas(): iterable
    {
        $areaClass = $this->em->getClassMetadata(AreaModule::class)
            ->getAssociationMapping('area')
            ->targetEntity;

        if (AreaInterface::class === $areaClass || !class_exists($areaClass)) {
            return [];
        }

        $identifier = $this->em->getClassMetadata($areaClass)->getSingleIdentifierFieldName();

        /** @var iterable<AreaInterface> $areas */
        $areas = $this->em->getRepository($areaClass)->findBy([], [$identifier => 'ASC']);

        return $areas;
    }
}
