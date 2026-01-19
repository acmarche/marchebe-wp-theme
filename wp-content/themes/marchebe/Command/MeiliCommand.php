<?php

namespace AcMarche\Theme\Command;

use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Search\DataForSearch;
use AcMarche\Theme\Lib\Search\MeiliServer;
use AcMarche\Theme\Repository\AdlRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'meili:server',
    description: ' ',
)]
class MeiliCommand extends Command
{
    private DataForSearch $dataForSearch;
    private readonly MeiliServer $meiliServer;

    protected function configure(): void
    {
        $this->setDescription('Manage server meilisearch');
        $this->addOption('key', "key", InputOption::VALUE_NONE, 'Create a key');
        $this->addOption('tasks', "tasks", InputOption::VALUE_NONE, 'Display tasks');
        $this->addOption('reset', "reset", InputOption::VALUE_NONE, 'Search engine reset');
        $this->addOption('update', "update", InputOption::VALUE_NONE, 'Update data');
        $this->addOption('dump', "dump", InputOption::VALUE_NONE, 'migrate data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = (bool)$input->getOption('key');
        $tasks = (bool)$input->getOption('tasks');
        $reset = (bool)$input->getOption('reset');
        $update = (bool)$input->getOption('update');
        $dump = (bool)$input->getOption('dump');

        $this->meiliServer = new MeiliServer();
        $this->meiliServer->initClientAndIndex();

        if ($key) {
            dump($this->meiliServer->createApiKey());

            return Command::SUCCESS;
        }

        if ($tasks) {
            $this->tasks($output);

            return Command::SUCCESS;
        }

        if ($reset) {
            $result = $this->meiliServer->createIndex();
            dump($result);
            $result = $this->meiliServer->settings();
            dump($result);

            return Command::SUCCESS;
        }

        if ($update) {
            $this->dataForSearch = new DataForSearch();

            $output->writeln('<info>Indexing posts...</info>');
            $this->indexPosts($output);
            $this->freeMemory();

            $output->writeln('<info>Indexing categories...</info>');
            $this->indexCategories($output);
            $this->freeMemory();

            $output->writeln('<info>Indexing bottin...</info>');
            $this->indexBottin($output);
            $this->freeMemory();

            $output->writeln('<info>Indexing enquetes...</info>');
            $this->indexEnquetes($output);
            $this->freeMemory();

            $output->writeln('<info>Indexing publications...</info>');
            $this->indexPublications($output);
            $this->freeMemory();

            $output->writeln('<info>Indexing ADL...</info>');
            $this->indexAdl($output);

            $output->writeln('<comment>Indexation complete!</comment>');

            return Command::SUCCESS;
        }

        if ($dump) {
            dump($this->meiliServer->dump());
        }

        return Command::SUCCESS;
    }

    private function indexPosts(OutputInterface $output): void
    {
        $documents = [];
        foreach (Theme::SITES as $idSite => $nom) {
            switch_to_blog($idSite);
            $posts = $this->dataForSearch->getPosts($idSite);
            $output->writeln(sprintf('  - %s: %d posts', $nom, count($posts)));
            foreach ($posts as $document) {
                $documents[] = $document;
            }
            unset($posts);
            restore_current_blog();
            $this->freeMemory();
        }

        $this->indexInBatches($documents, $output);
    }

    private function indexCategories(OutputInterface $output): void
    {
        $documents = [];
        foreach (Theme::SITES as $idSite => $nom) {
            switch_to_blog($idSite);
            $categories = $this->dataForSearch->getCategoriesBySite($idSite);
            $output->writeln(sprintf('  - %s: %d categories', $nom, count($categories)));
            foreach ($categories as $document) {
                $documents[] = $document;
            }
            unset($categories);
            restore_current_blog();
            $this->freeMemory();
        }
        $this->indexInBatches($documents, $output);
    }

    private function indexBottin(OutputInterface $output): void
    {
        $documents = $this->dataForSearch->fiches();
        $output->writeln(sprintf('  - %d fiches', count($documents)));
        $categories = $this->dataForSearch->indexCategoriesBottin();
        $output->writeln(sprintf('  - %d categories bottin', count($categories)));
        $this->indexInBatches(array_merge($documents, $categories), $output);
    }

    private function indexEnquetes(OutputInterface $output): void
    {
        $documents = [];
        switch_to_blog(Theme::ADMINISTRATION);
        foreach ($this->dataForSearch->getEnqueteDocuments() as $document) {
            $documents[] = $document;
        }
        restore_current_blog();
        $output->writeln(sprintf('  - %d enquetes', count($documents)));
        $this->indexInBatches($documents, $output);
    }

    private function indexPublications(OutputInterface $output): void
    {
        $documents = [];
        switch_to_blog(Theme::ADMINISTRATION);
        foreach ($this->dataForSearch->getAllPublications() as $document) {
            $documents[] = $document;
        }
        restore_current_blog();
        $output->writeln(sprintf('  - %d publications', count($documents)));
        $this->indexInBatches($documents, $output);
    }

    private function indexAdl(OutputInterface $output): void
    {
        $documents = [];
        $adlIndexer = new AdlRepository();
        foreach ($adlIndexer->getAllCategories() as $document) {
            $documents[] = $document;
        }

        foreach ($adlIndexer->getAllPosts() as $document) {
            $documents[] = $document;
        }
        $output->writeln(sprintf('  - %d ADL documents', count($documents)));
        $this->indexInBatches($documents, $output);
    }

    private function indexInBatches(array $documents, OutputInterface $output, int $batchSize = 500): void
    {
        $chunks = array_chunk($documents, $batchSize);
        foreach ($chunks as $i => $batch) {
            $this->meiliServer->index->addDocuments($batch, $this->meiliServer->primaryKey);
        }
        unset($documents, $chunks);
    }

    private function freeMemory(): void
    {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    private function tasks(OutputInterface $output): void
    {
        $tasks = $this->meiliServer->client->getTasks();
        $data = [];
        foreach ($tasks->getResults() as $result) {
            $t = [$result['uid'], $result['status'], $result['type'], $result['startedAt']];
            $t['error'] = null;
            $t['url'] = null;
            if ($result['status'] == 'failed') {
                if (isset($result['error'])) {
                    $t['error'] = $result['error']['message'];
                    $t['link'] = $result['error']['link'];
                }
            }
            $data[] = $t;
        }
        $table = new Table($output);
        $table
            ->setHeaders(['Uid', 'status', 'Type', 'Date', 'Error', 'Url'])
            ->setRows($data);
        $table->render();
    }
}