<?php

namespace AcMarche\Theme\Command;

use AcMarche\Theme\Inc\RouterBottin;
use AcMarche\Theme\Inc\RouterEnquete;
use AcMarche\Theme\Inc\RouterEvent;
use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Repository\WpRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'marche:integrity',
    description: ' ',
)]
class IntegrityCommand extends Command
{
    private SymfonyStyle $io;
    private OutputInterface $output;

    protected function configure(): void
    {
        $this->setDescription('To fix something');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
        $wpRepository = new WpRepository();

        $this->flushRoutes();

        /* $this->listRoutes();

         foreach (Theme::SITES as $idSite => $nom) {
             switch_to_blog($idSite);
             foreach (get_categories() as $category) {
                 $posts = $wpRepository->getPostsAndFiches($category->cat_ID);
                 if (count($posts) === 0) {
                     $this->io->writeln($category->name);
                     $this->io->writeln(get_category_link($category));
                 }
             }
         }*/

        return Command::SUCCESS;
    }

    private function listRoutes(): void
    {
        global $wp_rewrite;
        switch_to_blog(Theme::TOURISME);
        $routes = $wp_rewrite->wp_rewrite_rules();
        foreach ($routes as $route) {
            $this->io->writeln($route);
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