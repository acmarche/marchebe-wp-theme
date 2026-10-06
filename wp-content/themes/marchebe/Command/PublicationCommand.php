<?php

namespace AcMarche\Theme\Command;

use AcMarche\Theme\Lib\Mailer;
use AcMarche\Theme\Repository\PublicationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'deliberations:publications',
    description: 'Fetch and cache the publications of deliberations.be',
)]
class PublicationCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('no-pdf', null, InputOption::VALUE_NONE, 'Skip detail pages (no PDF links)');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Fetch and display without saving the list (PDF links are still cached)');
        $this->addOption('show', null, InputOption::VALUE_NONE, 'Display the cached publications, fetch nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repository = new PublicationRepository();

        if ($input->getOption('show')) {
            $this->display($output, $repository->findAll());

            return Command::SUCCESS;
        }

        try {
            $publications = $repository->fetchAll(
                fn(int $page, int $count) => $io->writeln("Page $page: $count publications")
            );
        } catch (\Throwable $e) {
            $io->error('Listing error '.$e->getMessage());
            Mailer::sendError('deliberations publications', $e->getMessage());

            return Command::FAILURE;
        }

        // zero resultat: plus probablement un changement de HTML qu'une liste vide, on garde l'ancien cache
        if ($publications === []) {
            $io->error('No publication found, cache left untouched');
            Mailer::sendError('deliberations publications', 'No publication found, markup changed?');

            return Command::FAILURE;
        }

        try {
            $natures = $repository->fetchNatures();
        } catch (\Throwable $e) {
            $io->warning('Natures error '.$e->getMessage());
            $natures = [];
        }
        // sans la liste du site, on garde la derniere connue pour ne pas perdre le filtrage des categories
        $fetchedNatures = $natures !== [];
        if (!$fetchedNatures) {
            $natures = $repository->findNatures();
        }
        $publications = PublicationRepository::addNatureSlugs($publications, $natures);
        $io->writeln('Natures: '.count($natures).($fetchedNatures ? '' : ' (from cache)'));

        $unmatched = array_unique(array_filter(array_map(
            fn(array $p) => $p['nature'] && !$p['nature_slug'] ? $p['nature'] : null,
            $publications
        )));
        if ($unmatched !== []) {
            $io->warning('Natures without slug, not filterable: '.implode(', ', $unmatched));
        }

        if (!$input->getOption('no-pdf')) {
            $fetched = 0;
            foreach ($publications as &$publication) {
                try {
                    $publication['pdf'] = $repository->fetchPdfUrl($publication['url'], $fromCache);
                    $fromCache || $fetched++;
                } catch (\Throwable $e) {
                    $io->warning('PDF error '.$publication['url'].' '.$e->getMessage());
                }
            }
            unset($publication);
            $io->writeln("Detail pages fetched: $fetched");
        }

        $this->display($output, $publications);

        if ($input->getOption('dry-run')) {
            $io->note('Dry run, cache not written');

            return Command::SUCCESS;
        }

        $repository->save($publications);
        if ($fetchedNatures) {
            $repository->saveNatures($natures);
        }
        $io->success(count($publications).' publications cached');

        return Command::SUCCESS;
    }

    private function display(OutputInterface $output, array $publications): void
    {
        $rows = array_map(fn(array $p) => [
            $p['date'],
            mb_strimwidth($p['title'], 0, 60, '…'),
            $p['nature'],
            $p['pdf'] ? 'oui' : '',
        ], $publications);

        (new Table($output))
            ->setHeaders(['Date', 'Titre', 'Nature', 'PDF'])
            ->setRows($rows)
            ->render();
    }
}
