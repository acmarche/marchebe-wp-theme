<?php

namespace AcMarche\Theme\Command;

use AcMarche\Theme\Inc\Theme;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Finds the uploads that post content still points at but that no longer exist
 * (file missing on disk, or attachment missing from the media library), and puts
 * them back from a file backup.
 *
 * For each missing file it copies the original back, re-creates the attachment
 * (under its old id when the content names it and the id is free, so the
 * wp-image-N classes and block ids keep working), regenerates its sizes, then
 * copies any derived file the content uses that the regeneration did not produce.
 *
 * With --old-db (a copy of the database from before the files were lost) it also
 * puts back the featured images: WordPress drops _thumbnail_id when it deletes an
 * attachment, so only the old database still knows them. Attachments are then
 * re-created from their old row (id, title, caption, alt text, date, parent).
 */
#[AsCommand(
    name: 'media:restore',
    description: 'Restore uploads referenced in content but missing, from a file backup',
)]
class RestoreMediaCommand extends Command
{
    private SymfonyStyle $io;
    private string $restoreDir;

    /** @var array<string,int> site path ('/administration/') => blog id */
    private array $sitePaths = [];

    /**
     * Missing uploads, keyed by "blogId|normalized key".
     *
     * @var array<string,array{site:int,rel:string,ids:array<int,true>,posts:array<int,string>,thumbs:array<int,int>}>
     */
    private array $missing = [];

    /** Connection to the database from before the loss, when --old-db is given. */
    private ?\wpdb $oldDb = null;

    /** @var array<int,array<string,object>> blog id => normalized key => old attachment row (with ->alt) */
    private array $oldAttachments = [];

    protected function configure(): void
    {
        $this->addOption('restore-dir', null, InputOption::VALUE_REQUIRED, 'Backup root holding uploads/ and blogs.dir/', '/var/restore');
        $this->addOption('site', null, InputOption::VALUE_REQUIRED, 'Only check this blog id');
        $this->addOption('old-db', null, InputOption::VALUE_REQUIRED, 'Database copy from before the loss (same server): restores featured images and attachment details');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Actually restore (otherwise only list)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->restoreDir = rtrim((string)$input->getOption('restore-dir'), '/');
        $force = (bool)$input->getOption('force');
        $onlySite = $input->getOption('site') !== null ? (int)$input->getOption('site') : null;

        if ($oldDbName = $input->getOption('old-db')) {
            $this->oldDb = new \wpdb(DB_USER, DB_PASSWORD, (string)$oldDbName, DB_HOST);
            if (!$this->oldDb->ready) {
                $this->io->error('Cannot connect to database '.$oldDbName);

                return Command::FAILURE;
            }
            $this->oldDb->suppress_errors();
        }

        foreach (get_sites(['number' => 0]) as $site) {
            $this->sitePaths[$site->path] = (int)$site->blog_id;
        }

        // A file may be embedded from another site: references are collected on every site
        // and grouped under the site that owns the file.
        foreach (array_keys(Theme::SITES) as $idSite) {
            switch_to_blog($idSite);
            $this->collect($idSite);
            if ($this->oldDb) {
                $this->collectThumbnails($idSite);
            }
            restore_current_blog();
        }

        if ($onlySite !== null) {
            $this->missing = array_filter($this->missing, fn($m) => $m['site'] === $onlySite);
        }

        if ($force) {
            // Re-create the sizes under their original JPEG/PNG names, the ones the content uses.
            remove_all_filters('image_editor_output_format');
            // The theme's save_post hook would push every restored attachment into MeiliSearch.
            remove_all_actions('save_post');
            require_once ABSPATH.'wp-admin/includes/image.php';
        }

        $rows = [];
        $counts = ['restored' => 0, 'files only' => 0, 'thumbnail only' => 0, 'not in backup' => 0, 'failed' => 0];
        ksort($this->missing);
        foreach ($this->missing as $item) {
            switch_to_blog($item['site']);
            [$status, $detail] = $this->handle($item, $force);
            restore_current_blog();
            $counts[$status]++;
            $used = array_map(
                fn($label) => in_array((int)explode(':', $label)[1], $item['thumbs'], true) && (int)explode(':', $label)[0] === $item['site'] ? $label.'*' : $label,
                $item['posts']
            );
            $rows[] = [
                Theme::SITES[$item['site']] ?? $item['site'],
                $item['rel'],
                implode(',', array_keys($item['ids'])) ?: '-',
                implode(' ', array_slice($used, 0, 3)).(count($used) > 3 ? ' …' : ''),
                $status.($detail ? ' ('.$detail.')' : ''),
            ];
        }

        $this->io->table(['Site', 'File', 'Old id', 'Used by', $force ? 'Result' : 'Would'], $rows);
        foreach ($counts as $label => $n) {
            $this->io->writeln(sprintf('%-14s %d', $label, $n));
        }
        if ($this->oldDb) {
            $this->io->writeln('* = featured image of that post');
        }
        if (!$force) {
            $this->io->note('Dry run: nothing changed. Re-run with --force to restore.');
        }

        return Command::SUCCESS;
    }

    /**
     * Records every upload url in the content of the current site whose file or attachment is gone.
     */
    private function collect(int $idSite): void
    {
        global $wpdb;

        $posts = $wpdb->get_results(
            "SELECT ID, post_type, post_content, post_excerpt FROM $wpdb->posts
             WHERE post_type NOT IN ('attachment','revision') AND post_status <> 'auto-draft'"
        );

        foreach ($posts as $post) {
            $text = str_replace('\\/', '/', $post->post_content.' '.$post->post_excerpt);
            $label = $idSite.':'.$post->ID;

            // Ids the content gives a url: <img class="wp-image-N" src> and file blocks.
            $idByUrl = [];
            if (preg_match_all('/<img\b[^>]*>/i', $text, $tags)) {
                foreach ($tags[0] as $tag) {
                    if (preg_match('/wp-image-(\d+)/', $tag, $id) && preg_match('/\ssrc=["\']([^"\']+)/', $tag, $src)) {
                        $idByUrl[$src[1]] = (int)$id[1];
                    }
                }
            }
            if (preg_match_all('/<!-- wp:(?:file|video|audio) \{[^}]*"id":(\d+)[^}]*"(?:href|src)":"([^"]+)"/', $text, $blocks, PREG_SET_ORDER)) {
                foreach ($blocks as $block) {
                    $idByUrl[$block[2]] = (int)$block[1];
                }
            }

            if (!preg_match_all('#(?:https?://[a-z0-9.-]+)?(/[^\s"\'<>()]*?/(?:files|uploads)/[^\s"\'<>?\#()\]\\\\]+)#i', $text, $urls, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($urls as [$full, $path]) {
                if (!$this->isOurHost($full)) {
                    continue;
                }
                $located = $this->locate($path, $idSite);
                if ($located === null) {
                    continue;
                }
                [$site, $rel] = $located;
                if ($this->isPresent($site, $rel)) {
                    continue;
                }
                $key = $site.'|'.FixCommand::normalizeFile($rel);
                $this->missing[$key] ??= ['site' => $site, 'rel' => $rel, 'ids' => [], 'posts' => [], 'thumbs' => []];
                if (isset($idByUrl[$full]) && $site === $idSite) {
                    $this->missing[$key]['ids'][$idByUrl[$full]] = true;
                }
                if (!in_array($label, $this->missing[$key]['posts'], true)) {
                    $this->missing[$key]['posts'][] = $label;
                }
            }
        }
    }

    /**
     * Featured images the old database records whose attachment is gone and that the post
     * no longer has (WordPress removes _thumbnail_id together with the attachment).
     */
    private function collectThumbnails(int $idSite): void
    {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix($idSite);
        $rows = $this->oldDb->get_results(
            "SELECT m.post_id, m.meta_value AS thumb, f.meta_value AS file
             FROM {$prefix}postmeta m
             JOIN {$prefix}posts a ON a.ID = m.meta_value AND a.post_type = 'attachment'
             JOIN {$prefix}postmeta f ON f.post_id = a.ID AND f.meta_key = '_wp_attached_file'
             WHERE m.meta_key = '_thumbnail_id'"
        ) ?: [];

        foreach ($rows as $row) {
            $postId = (int)$row->post_id;
            $thumb = (int)$row->thumb;
            if (get_post_type($thumb) === 'attachment' || !get_post($postId)) {
                continue; // image still there, or the post itself is gone
            }
            $current = (int)get_post_meta($postId, '_thumbnail_id', true);
            if ($current && get_post_type($current) === 'attachment') {
                continue; // an editor has chosen another image since
            }
            $rel = (string)$row->file;
            $key = $idSite.'|'.FixCommand::normalizeFile($rel);
            $this->missing[$key] ??= ['site' => $idSite, 'rel' => $rel, 'ids' => [], 'posts' => [], 'thumbs' => []];
            $this->missing[$key]['ids'][$thumb] = true;
            $this->missing[$key]['thumbs'][] = $postId;
            $label = $idSite.':'.$postId;
            if (!in_array($label, $this->missing[$key]['posts'], true)) {
                $this->missing[$key]['posts'][] = $label;
            }
        }
    }

    /** The attachment row the old database holds for this file, if any. */
    private function oldAttachment(int $site, string $key): ?object
    {
        if (!$this->oldDb) {
            return null;
        }
        if (!isset($this->oldAttachments[$site])) {
            global $wpdb;
            $prefix = $wpdb->get_blog_prefix($site);
            $this->oldAttachments[$site] = [];
            $rows = $this->oldDb->get_results(
                "SELECT a.*, f.meta_value AS attached_file, alt.meta_value AS alt
                 FROM {$prefix}posts a
                 JOIN {$prefix}postmeta f ON f.post_id = a.ID AND f.meta_key = '_wp_attached_file'
                 LEFT JOIN {$prefix}postmeta alt ON alt.post_id = a.ID AND alt.meta_key = '_wp_attachment_image_alt'
                 WHERE a.post_type = 'attachment'"
            ) ?: [];
            foreach ($rows as $row) {
                $this->oldAttachments[$site][FixCommand::normalizeFile((string)$row->attached_file)] = $row;
            }
        }

        return $this->oldAttachments[$site][$key] ?? null;
    }

    private function isOurHost(string $url): bool
    {
        if (!preg_match('#^https?://([^/]+)#i', $url, $m)) {
            return true; // relative url
        }
        $host = strtolower($m[1]);

        return in_array($host, ['www.marche.be', 'marche.be', strtolower($_ENV['WP_URL_HOME'] ?? '')], true);
    }

    /**
     * Maps an url path to the site owning the file and the path relative to that site's upload dir.
     *
     * @return array{int,string}|null
     */
    private function locate(string $path, int $currentSite): ?array
    {
        $path = rawurldecode($path);
        if (preg_match('#/wp-content/uploads/sites/(\d+)/(.+)$#', $path, $m)) {
            return [(int)$m[1], $m[2]];
        }
        if (preg_match('#/wp-content/uploads/(.+)$#', $path, $m)) {
            return [1, $m[1]];
        }
        if (preg_match('#/wp-content/blogs\.dir/(\d+)/files/(.+)$#', $path, $m)) {
            return [(int)$m[1], $m[2]];
        }
        // ms-files rewrite: /administration/files/… or /files/…
        if (preg_match('#^(/(?:[^/]+/)?)files/(.+)$#', $path, $m)) {
            $site = $this->sitePaths[$m[1]] ?? ($m[1] === '/' ? $currentSite : null);

            return $site ? [$site, $m[2]] : null;
        }

        return null;
    }

    private function liveDir(int $site): string
    {
        return $site === 1 ? WP_CONTENT_DIR.'/uploads' : WP_CONTENT_DIR.'/blogs.dir/'.$site.'/files';
    }

    private function backupDir(int $site): string
    {
        return $site === 1 ? $this->restoreDir.'/uploads' : $this->restoreDir.'/blogs.dir/'.$site.'/files';
    }

    /** Present = the file exists on disk and an attachment of its site holds it. */
    private function isPresent(int $site, string $rel): bool
    {
        return is_file($this->liveDir($site).'/'.$rel) && $this->findAttachment($site, $rel) !== null;
    }

    /** @var array<int,array<string,int>> blog id => normalized key => attachment id */
    private array $attachmentKeys = [];

    private function findAttachment(int $site, string $rel): ?int
    {
        if (!isset($this->attachmentKeys[$site])) {
            global $wpdb;
            $prefix = $wpdb->get_blog_prefix($site);
            $this->attachmentKeys[$site] = [];
            foreach ($wpdb->get_results("SELECT post_id, meta_value FROM {$prefix}postmeta WHERE meta_key = '_wp_attached_file'") as $row) {
                $this->attachmentKeys[$site][FixCommand::normalizeFile((string)$row->meta_value)] = (int)$row->post_id;
            }
        }

        return $this->attachmentKeys[$site][FixCommand::normalizeFile($rel)] ?? null;
    }

    /**
     * @param array{site:int,rel:string,ids:array<int,true>,posts:array<int,string>,thumbs:array<int,int>} $item
     * @return array{string,string}
     */
    private function handle(array $item, bool $force): array
    {
        $site = $item['site'];
        $live = $this->liveDir($site);
        $backup = $this->backupDir($site);
        $dir = dirname($item['rel']);
        $key = FixCommand::normalizeFile($item['rel']);
        $attachmentId = $this->findAttachment($site, $item['rel']);
        $thumbs = $this->thumbsToSet($item['thumbs'], $attachmentId);
        $thumbNote = $thumbs ? ', featured image of '.count($thumbs).' post'.(count($thumbs) > 1 ? 's' : '') : '';

        // Only the featured image link is missing: the attachment and its file are back already.
        if ($attachmentId && is_file($live.'/'.$item['rel'])) {
            if (!$thumbs) {
                return ['files only', 'nothing to do'];
            }
            if ($force) {
                $this->setThumbs($thumbs, $attachmentId);
            }

            return ['thumbnail only', '#'.$attachmentId.$thumbNote];
        }

        // The original and every derived file sharing its key, as found in the backup.
        $siblings = [];
        foreach (glob($backup.'/'.$dir.'/*') ?: [] as $file) {
            if (is_file($file) && FixCommand::normalizeFile($dir.'/'.basename($file)) === $key) {
                $siblings[] = basename($file);
            }
        }
        $original = $this->pickOriginal($siblings, $item['rel']);
        if ($original === null) {
            return ['not in backup', ''];
        }

        $old = $this->oldAttachment($site, $key);
        $status = $attachmentId ? 'files only' : 'restored';
        $wantedId = $old ? (int)$old->ID : array_key_first($item['ids']);
        $reuseId = $wantedId && !get_post($wantedId) ? $wantedId : null;

        if (!$force) {
            $detail = $attachmentId ? 'attachment #'.$attachmentId : ($reuseId ? 'as #'.$reuseId : 'new id');

            return [$status, $detail.($old && !$attachmentId ? ', old details' : '').$thumbNote];
        }

        if (!is_dir($live.'/'.$dir) && !wp_mkdir_p($live.'/'.$dir)) {
            return ['failed', 'cannot create '.$dir];
        }
        if (!is_writable($live.'/'.$dir)) {
            return ['failed', $dir.' not writable, run as the web server user'];
        }
        $originalPath = $live.'/'.$dir.'/'.$original;
        if (!is_file($originalPath) && !copy($backup.'/'.$dir.'/'.$original, $originalPath)) {
            return ['failed', 'copy '.$original];
        }

        if (!$attachmentId) {
            $attachmentId = $this->createAttachment($item, $dir, $original, $originalPath, $old, $reuseId);
            if (is_wp_error($attachmentId)) {
                return ['failed', $attachmentId->get_error_message()];
            }
            $this->attachmentKeys[$site][$key] = $attachmentId;
        }

        // Derived files the content may use that regeneration did not produce (old sizes, edits).
        $copied = 0;
        foreach ($siblings as $file) {
            $target = $live.'/'.$dir.'/'.$file;
            if (!is_file($target) && copy($backup.'/'.$dir.'/'.$file, $target)) {
                $copied++;
            }
        }

        $this->setThumbs($this->thumbsToSet($item['thumbs'], $attachmentId), $attachmentId);

        if (!is_file($live.'/'.$item['rel'])) {
            return ['failed', '#'.$attachmentId.' but '.basename($item['rel']).' still missing'];
        }

        return [$status, '#'.$attachmentId.($copied ? ', +'.$copied.' files' : '').$thumbNote];
    }

    /**
     * Inserts the attachment, from its old row when the old database has it.
     *
     * @param array{site:int,rel:string,ids:array<int,true>,posts:array<int,string>,thumbs:array<int,int>} $item
     */
    private function createAttachment(array $item, string $dir, string $original, string $originalPath, ?object $old, ?int $reuseId): int|\WP_Error
    {
        [$firstSite, $firstPost] = array_map('intval', explode(':', $item['posts'][0]));
        $parent = $firstSite === $item['site'] ? $firstPost : 0;

        $attachment = [
            'post_mime_type' => wp_check_filetype($original)['type'] ?: 'application/octet-stream',
            'post_title' => preg_replace('/\.[^.]+$/', '', $original),
            'post_content' => '',
            'post_status' => 'inherit',
            'guid' => wp_upload_dir()['baseurl'].'/'.$dir.'/'.$original,
        ];
        if (preg_match('#^(\d{4})/(\d{2})$#', $dir, $m)) {
            $attachment['post_date'] = sprintf('%s-%s-01 00:00:00', $m[1], $m[2]);
        }
        if ($old) {
            $attachment = array_merge($attachment, [
                'post_title' => $old->post_title,
                'post_content' => $old->post_content,
                'post_excerpt' => $old->post_excerpt,
                'post_name' => $old->post_name,
                'post_author' => (int)$old->post_author,
                'post_date' => $old->post_date,
                'post_date_gmt' => $old->post_date_gmt,
                'menu_order' => (int)$old->menu_order,
            ]);
            if ((int)$old->post_parent && get_post((int)$old->post_parent)) {
                $parent = (int)$old->post_parent;
            }
        }
        if ($reuseId) {
            $attachment['import_id'] = $reuseId;
        }

        $attachmentId = wp_insert_attachment($attachment, $originalPath, $parent, true);
        if (is_wp_error($attachmentId)) {
            return $attachmentId;
        }
        wp_update_attachment_metadata($attachmentId, wp_generate_attachment_metadata($attachmentId, $originalPath));
        if ($old && $old->alt !== null && $old->alt !== '') {
            update_post_meta($attachmentId, '_wp_attachment_image_alt', wp_slash($old->alt));
        }

        return $attachmentId;
    }

    /**
     * The posts among $postIds that still have no usable featured image.
     *
     * @param int[] $postIds
     * @return int[]
     */
    private function thumbsToSet(array $postIds, ?int $attachmentId): array
    {
        return array_values(array_filter($postIds, function (int $postId) use ($attachmentId) {
            $current = (int)get_post_meta($postId, '_thumbnail_id', true);

            return $current !== $attachmentId && !($current && get_post_type($current) === 'attachment');
        }));
    }

    /** @param int[] $postIds */
    private function setThumbs(array $postIds, int $attachmentId): void
    {
        foreach ($postIds as $postId) {
            update_post_meta($postId, '_thumbnail_id', $attachmentId);
        }
    }

    /**
     * The upload itself among the files sharing a key: the one whose name carries no derived
     * suffix, preferably with the referenced file's extension (a PDF beats its -pdf.jpg previews).
     *
     * @param string[] $siblings
     */
    private function pickOriginal(array $siblings, string $rel): ?string
    {
        $originals = array_values(array_filter(
            $siblings,
            fn($f) => !preg_match(FixCommand::DERIVED_SUFFIX, preg_replace('/\.[^.]+$/', '', $f))
        ));
        if (!$originals) {
            // Only a -scaled copy survived: it is a full-size image, use it.
            $originals = array_values(array_filter($siblings, fn($f) => str_contains($f, '-scaled.')));
        }
        if (!$originals) {
            return null;
        }
        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
        foreach ($originals as $f) {
            if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === $ext) {
                return $f;
            }
        }
        // A PDF preview (…-pdf-724x1024.jpg) points to the PDF; otherwise any original.
        usort($originals, fn($a, $b) => str_ends_with(strtolower($b), '.pdf') <=> str_ends_with(strtolower($a), '.pdf'));

        return $originals[0];
    }
}
