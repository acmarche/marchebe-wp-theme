<?php

namespace AcMarche\Theme\Command;

use AcMarche\Theme\Inc\RouterBottin;
use AcMarche\Theme\Inc\RouterEnquete;
use AcMarche\Theme\Inc\RouterEvent;
use AcMarche\Theme\Inc\Theme;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'marche:integrity',
    description: ' ',
)]
class IntegrityCommand extends Command
{
    private SymfonyStyle $io;

    protected function configure(): void
    {
        $this
            ->setDescription('List or flush rewrite rules')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List all rewrite rules')
            ->addOption('flush', 'f', InputOption::VALUE_NONE, 'Flush rewrite rules');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($input->getOption('flush')) {
            $this->flushRoutes();
            $this->io->success('Rewrite rules flushed');
        } elseif ($input->getOption('list')) {
            $this->listRoutes();
        } else {
            $this->io->warning('Please specify --list or --flush');
        }

        return Command::SUCCESS;
    }

    private function listRoutes(): void
    {
        global $wp_rewrite;
        foreach (Theme::SITES as $idSite => $nom) {
            $this->io->title($nom);
            switch_to_blog($idSite);
            $routes = $wp_rewrite->wp_rewrite_rules();
            foreach ($routes as $route) {
                $this->io->writeln($route);
            }
        }
    }

    public function flushRoutes(): void
    {
        foreach (Theme::SITES as $site) {
            switch_to_blog($site);
            new RouterBottin();
        }

        switch_to_blog(Theme::TOURISME);
        new RouterEvent();

        switch_to_blog(Theme::ADMINISTRATION);
        new RouterEnquete();

        switch_to_blog(Theme::CITOYEN);
        if (is_multisite()) {
            $current = get_current_blog_id();
            foreach (Theme::SITES as $site) {
                switch_to_blog($site);
                flush_rewrite_rules();
            }
            switch_to_blog($current);
        } else {
            flush_rewrite_rules();
        }
    }

}