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
    /**
     * Suffixes WordPress appends to an upload's file name for its derived files
     * (sizes, big-image scaling, rotation, edits, PDF previews).
     */
    private const DERIVED_SUFFIX = '/-(?:\d+x\d+|scaled|rotated|e\d{10,}|pdf)$/';

    private SymfonyStyle $io;

    /**
     * Upload paths referenced anywhere, on any site, normalized by normalizeFile().
     * Shared across sites: a post may embed a file uploaded on another site.
     *
     * @var array<string,true>
     */
    private array $usedFiles = [];

    /**
     * Attachment ids referenced on each site (ids are only unique per site).
     *
     * @var array<int,array<int,true>>
     */
    private array $usedIds = [];

    /**
     * Posts holding a [gallery] without ids: it shows every image attached to the post.
     *
     * @var array<int,array<int,true>>
     */
    private array $galleryParents = [];

    protected function configure(): void
    {
        $this->setDescription('To cleaning medias');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Actually delete the unused attachments (otherwise only list them)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // References must be collected on every site before any attachment is judged.
        foreach (Theme::SITES as $idSite => $nom) {
            switch_to_blog($idSite);
            $this->collectReferences($idSite);
            restore_current_blog();
        }
        $this->io->success(count($this->usedFiles).' distinct upload files referenced');

        $attachmentsToDelete = [];
        $rows = [];

        foreach (Theme::SITES as $idSite => $nom) {
            switch_to_blog($idSite);

            // WP_Query ignores "numberposts": without posts_per_page it only returns one page.
            $attachments = get_posts([
                'post_type' => 'attachment',
                'post_status' => 'any',
                'posts_per_page' => -1,
                'orderby' => 'ID',
                'order' => 'ASC',
            ]);

            $unused = $unverifiable = 0;
            foreach ($attachments as $attachment) {
                $used = $this->checkAttachmentIsUsed($idSite, $attachment);
                if ($used === null) {
                    $unverifiable++;
                } elseif ($used === false) {
                    $unused++;
                    $attachmentsToDelete[] = [$idSite, $attachment];
                }
            }
            $rows[] = [$nom, count($attachments), $unused, $unverifiable];
            restore_current_blog();
        }

        $force = (bool)$input->getOption('force');

        foreach ($attachmentsToDelete as [$idSite, $attachment]) {
            $this->io->writeln(Theme::SITES[$idSite].' #'.$attachment->ID.' '.$attachment->guid);
            if ($force) {
                switch_to_blog($idSite);
                wp_delete_attachment($attachment->ID, true);
                restore_current_blog();
            }
        }

        $this->io->table(['Site', 'Attachments', 'Unused', 'Kept (no file recorded)'], $rows);
        $this->io->success(count($attachmentsToDelete).' attachments '.($force ? 'deleted' : 'to delete'));

        if (!$force) {
            $this->io->note('Dry run: nothing deleted. Re-run with --force to delete these attachments.');
        }

        return Command::SUCCESS;
    }

    /**
     * Records every upload referenced by the current site: in the content of any post type
     * and status (pages, drafts, private, trash, revisions, custom types), in post meta and
     * in options (widgets, theme mods).
     */
    private function collectReferences(int $idSite): void
    {
        global $wpdb;

        $this->usedIds[$idSite] = [];
        $this->galleryParents[$idSite] = [];

        $posts = $wpdb->get_results(
            "SELECT ID, post_content, post_excerpt FROM $wpdb->posts WHERE post_type <> 'attachment'"
        );
        foreach ($posts as $post) {
            $text = $post->post_content.' '.$post->post_excerpt;
            $this->collectFiles($text);
            $this->collectIds($idSite, $text);
            if (preg_match('/\[gallery(?![^\]]*\b(?:ids|include)=)[^\]]*\]/', $text)) {
                $this->galleryParents[$idSite][(int)$post->ID] = true;
            }
        }

        $metas = $wpdb->get_results(
            "SELECT pm.meta_key, pm.meta_value FROM $wpdb->postmeta pm
             INNER JOIN $wpdb->posts p ON p.ID = pm.post_id
             WHERE p.post_type <> 'attachment'"
        );
        foreach ($metas as $meta) {
            if ($meta->meta_key === '_thumbnail_id') {
                $this->usedIds[$idSite][(int)$meta->meta_value] = true;
                continue;
            }
            $this->collectFiles((string)$meta->meta_value);
        }

        $options = $wpdb->get_col(
            "SELECT option_value FROM $wpdb->options
             WHERE option_name NOT LIKE '\\_transient%' AND option_name NOT LIKE '\\_site\\_transient%'"
        );
        foreach ($options as $value) {
            $this->collectFiles((string)$value);
            // Media widgets, custom logo, header image, stored as serialized ids.
            if (preg_match_all('/"(?:attachment_id|custom_logo)";(?:i:|s:\d+:")(\d+)/', (string)$value, $matches)) {
                foreach ($matches[1] as $id) {
                    $this->usedIds[$idSite][(int)$id] = true;
                }
            }
        }
        if ($siteIcon = (int)get_option('site_icon')) {
            $this->usedIds[$idSite][$siteIcon] = true;
        }
    }

    /**
     * Upload urls: /wp-content/uploads/…, /wp-content/blogs.dir/N/files/… and the
     * multisite /files/… rewrite.
     */
    private function collectFiles(string $text): void
    {
        if (!str_contains($text, 'uploads') && !str_contains($text, 'files')) {
            return;
        }
        // Block attributes and serialized JSON escape their slashes.
        $text = str_replace('\\/', '/', $text);
        if (preg_match_all('#/(?:uploads(?:/sites/\d+)?|files)/([^"\'\s<>?\#)\]\\\\]+)#', $text, $matches)) {
            foreach ($matches[1] as $file) {
                $this->usedFiles[$this->normalizeFile($file)] = true;
            }
        }
    }

    /**
     * Attachment ids: image classes, block attributes, captions, attachment links, galleries.
     * Deliberately greedy: a number wrongly read as an id only keeps a file.
     */
    private function collectIds(int $idSite, string $text): void
    {
        $patterns = [
            '/wp-(?:image|att)-(\d+)/',
            '/attachment[_-](?:id=)?(\d+)/',
            '/"(?:id|mediaId|mediaID)"\s*:\s*"?(\d+)/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $id) {
                    $this->usedIds[$idSite][(int)$id] = true;
                }
            }
        }
        // Gallery blocks ("ids":[1,2]) and shortcodes (ids="1,2", include="1,2").
        if (preg_match_all('/(?:"ids"\s*:\s*\[|\b(?:ids|include)=["\'])([\d,\s"]+)/', $text, $matches)) {
            foreach ($matches[1] as $list) {
                foreach (preg_split('/\D+/', $list, -1, PREG_SPLIT_NO_EMPTY) as $id) {
                    $this->usedIds[$idSite][(int)$id] = true;
                }
            }
        }
    }

    /**
     * Reduces an upload path to the key shared by the original and all its derived files,
     * so "2020/01/photo-1024x768.jpg" and "2020/01/photo-scaled.jpg" both match "2020/01/photo.jpg".
     */
    private function normalizeFile(string $file): string
    {
        $file = rawurldecode($file);
        if (class_exists(\Normalizer::class)) {
            $file = \Normalizer::normalize($file, \Normalizer::FORM_C) ?: $file;
        }
        $file = mb_strtolower($file);
        $file = preg_replace('/\.[a-z0-9]{2,5}$/', '', $file);
        do {
            $file = preg_replace(self::DERIVED_SUFFIX, '', $file, -1, $count);
        } while ($count > 0);

        return $file;
    }

    private function ancestor()
    {
        switch_to_blog(Theme::ENFANCE);
        $post = get_post(894);
        $wpRepository = new WpRepository();
        $paths = $wpRepository->getAncestorsOfPost($post->ID);
        dd($paths);
    }

    /**
     * @return bool|null null when the attachment has no file recorded: it cannot be
     *                   checked, so it is kept.
     */
    private function checkAttachmentIsUsed(int $idSite, \WP_Post $attachment): ?bool
    {
        if (isset($this->usedIds[$idSite][$attachment->ID])) {
            return true;
        }
        if ($attachment->post_parent
            && isset($this->galleryParents[$idSite][$attachment->post_parent])
            && str_starts_with($attachment->post_mime_type, 'image/')) {
            return true;
        }

        $file = get_post_meta($attachment->ID, '_wp_attached_file', true);
        if (!is_string($file) || $file === '') {
            return null;
        }

        return isset($this->usedFiles[$this->normalizeFile($file)]);
    }

}
