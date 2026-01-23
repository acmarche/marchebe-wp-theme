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
        $current = get_current_blog_id();

        // First, delete all rewrite_rules to remove stale rules
        foreach (Theme::SITES as $idSite => $nom) {
            switch_to_blog($idSite);
            delete_option('rewrite_rules');
            $this->io->writeln("Deleted rewrite_rules for: $nom");
        }

        // Re-register rules directly (init hook doesn't fire in CLI)
        $routerBottin = new RouterBottin();
        $routerEvent = new RouterEvent();
        $routerEnquete = new RouterEnquete();

        foreach (Theme::SITES as $idSite => $nom) {
            switch_to_blog($idSite);
            $routerBottin->add_rewrite_rule();
        }

        switch_to_blog(Theme::TOURISME);
        $routerEvent->add_rewrite_rule();
        $this->io->writeln("Added RouterEvent rules for: Tourisme");

        switch_to_blog(Theme::ADMINISTRATION);
        $routerEnquete->add_rewrite_rule();
        $this->io->writeln("Added RouterEnquete rules for: Administration");

        // Flush to regenerate rules
        foreach (Theme::SITES as $idSite => $nom) {
            switch_to_blog($idSite);
            flush_rewrite_rules();
            $this->io->writeln("Flushed rules for: $nom");
        }

        switch_to_blog($current);
    }

}