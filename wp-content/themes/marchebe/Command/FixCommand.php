<?php

namespace AcMarche\Theme\Command;

use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Repository\WpRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'marche:fix',
    description: ' ',
)]
class FixCommand extends Command
{
    private SymfonyStyle $io;
    private array $allposts = [];

    protected function configure(): void
    {
        $this->setDescription('To cleaning medias');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Actually delete the unused attachments (otherwise only list them)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->allposts = $this->getAllPosts();
        $this->io->success(count($this->allposts).' posts');
        $attachmentsToDelete = [];

        foreach (Theme::SITES as $idSite => $nom) {
            switch_to_blog($idSite);

            $args = [
                'numberposts' => 5000,
                'post_status' => 'inherit',
                'post_type' => 'attachment',
            ];
            $query = new \WP_Query($args);

            while ($query->have_posts()) {
                $attachment = $query->next_post();
                if (!$this->checkAttachmentIsUsed($attachment)) {
                    $attachmentsToDelete[] = [$idSite, $attachment];
                }
            }
        }

        $force = (bool)$input->getOption('force');

        $this->io->success(count($attachmentsToDelete).' posts to delete');

        foreach ($attachmentsToDelete as [$idSite, $attachment]) {
            $this->io->writeln($attachment->guid);
            if ($force) {
                switch_to_blog($idSite);
                wp_delete_attachment($attachment->ID, true);
            }
        }

        if (!$force) {
            $this->io->note('Dry run: nothing deleted. Re-run with --force to delete these attachments.');
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int,\WP_Post>
     */
    private function getAllPosts(): array
    {
        $allPosts = [];

        foreach (Theme::SITES as $idSite => $nom) {
            switch_to_blog($idSite);
            $all = [];
            $posts = get_posts(['numberposts' => 10000]);
            foreach ($posts as $post) {
                $all[] = $post;
                if ($thumbnail = get_post_thumbnail_id($post->ID)) {
                    $all[] = get_post($thumbnail);
                }
            }

            $allPosts = [...$allPosts, ...$all];
        }

        return $allPosts;
    }

    private function ancestor()
    {
        switch_to_blog(Theme::ENFANCE);
        $post = get_post(894);
        $wpRepository = new WpRepository();
        $paths = $wpRepository->getAncestorsOfPost($post->ID);
        dd($paths);
    }

    private function checkAttachmentIsUsed(int|\WP_Post|null $attachment): bool
    {
        foreach ($this->allposts as $post) {
            if (str_contains($post->post_content, $attachment->guid)) {
                return true;
            }
            if (str_contains($post->guid, $attachment->guid)) {
                return true;
            }
        }

        return false;
    }

}