<?php

namespace AcMarche\Theme\Lib\Search;

use AcMarche\Theme\Inc\BottinCategoryMetaBox;
use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Bottin\Bottin;
use AcMarche\Theme\Repository\ApiRepository;
use AcMarche\Theme\Repository\BottinRepository;
use AcMarche\Theme\Repository\WpRepository;

class DataForSearch
{
    private WpRepository $wpRepository;
    private BottinRepository $bottinRepository;
    private static ?BottinRepository $bottinRepositoryStatic = null;
    private array $skips = [679, 705, 707];//parkings

    public function __construct()
    {
        $this->wpRepository = new WpRepository();
        $this->bottinRepository = new BottinRepository();
    }

    public static function instanceBottinRepository(): BottinRepository
    {
        if (!self::$bottinRepositoryStatic) {
            self::$bottinRepositoryStatic = new BottinRepository();
        }

        return self::$bottinRepositoryStatic;
    }

    /**
     * @param int $idSite
     * @param int|null $categoryId
     * @return array<int,Document>
     */
    public function getPosts(int $idSite, int $categoryId = null): array
    {
        $args = array(
            'numberposts' => 5000,
            'orderby' => 'post_title',
            'order' => 'ASC',
            'post_status' => 'publish',
            'suppress_filters' => true,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        if ($categoryId) {
            $args['category'] = $categoryId;
        }

        $posts = get_posts($args);
        $data = [];

        foreach ($posts as $post) {
            $this->wpRepository->preparePost($post);
            $data[] = Document::documentFromPost($post, $idSite, 'local');
        }

        // Free memory
        unset($posts);

        return $data;
    }

    /**
     * Lightweight method to get post content for category indexing
     * Does not create Document objects to save memory
     */
    private function getPostContentForCategory(int $categoryId): string
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT p.post_title, p.post_excerpt
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE tt.term_id = %d
             AND tt.taxonomy = 'category'
             AND p.post_status = 'publish'
             AND p.post_type = 'post'
             LIMIT 100",
            $categoryId
        );

        $posts = $wpdb->get_results($sql);
        $content = '';

        foreach ($posts as $post) {
            $content .= ' '.Cleaner::cleandata($post->post_title);
            if ($post->post_excerpt) {
                $content .= ' '.Cleaner::cleandata($post->post_excerpt);
            }
        }

        unset($posts);

        return $content;
    }

    /**
     * @param int $idSite
     * @return array<int,Document>
     */
    public function getCategoriesBySite(int $idSite): array
    {
        $args = array(
            'type' => 'post',
            'child_of' => 0,
            'parent' => '',
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => 0,
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'number' => '',
            'taxonomy' => 'category',
            'pad_counts' => false,
        );

        $categories = get_categories($args);
        $data = [];

        foreach ($categories as $category) {
            if ($category->description) {
                $category->description = Cleaner::cleandata($category->description);
            }

            // Use lightweight method - only titles and excerpts, limited to 100 posts
            $content = $category->description ?? '';
            $content .= $this->getPostContentForCategory($category->cat_ID);
            $content .= $this->getContentFichesBottin($category);

            $children = $this->wpRepository->getChildrenOfCategory($category->cat_ID);
            $tags = [];
            $parent = $this->wpRepository->getParentCategory($category->cat_ID);
            if ($parent) {
                $tags[] = ['id' => $parent->term_id, 'name' => $parent->name];
            }
            $category->content = $content;
            $category->tags = $tags;
            $category->link = get_category_link($category);
            $data[] = Document::documentFromCategory($category, $idSite, 'local');

            // Free memory after each category
            unset($content, $children, $tags);
        }

        unset($categories);

        return $data;
    }

    public function getContentFichesBottin(object $category): string
    {
        $categoryBottinId = get_term_meta($category->cat_ID, BottinCategoryMetaBox::KEY_NAME, true);

        if ($categoryBottinId) {
            $fiches = $this->bottinRepository->getFichesByCategory($categoryBottinId);

            return $this->getContentForCategory($fiches);
        }

        return '';
    }

    public static function getContentForCategory(array $fiches): string
    {
        $content = '';

        foreach ($fiches as $fiche) {
            $content .= self::getContentFiche($fiche);
        }

        return $content;
    }

    public static function getContentFiche($fiche): string
    {
        return ' '.$fiche->societe.' '.$fiche->email.' '.$fiche->website.' '.$fiche->twitter.' '.$fiche->facebook.' '.$fiche->nom.' '.$fiche->prenom.' '.$fiche->comment1.' '.$fiche->comment2.' '.$fiche->comment3;
    }

    /**
     * @param $fiche
     *
     * @return string[]
     */
    public static function getCategoriesFiche($fiche): array
    {
        $data = self::instanceBottinRepository()->getCategoriesOfFiche($fiche->id);
        $categories = [];
        foreach ($data as $category) {
            $categories[] = ['id' => $category->id, 'name' => $category->name];
        }

        return $categories;
    }

    /**
     * @return array<int,Document>
     * @throws \Exception
     */
    public function fiches(): array
    {
        $documents = [];

        foreach ($this->bottinRepository->getFiches() as $fiche) {
            $idWpSite = $this->bottinRepository->findByFicheIdWpSite($fiche);
            $root = $this->bottinRepository->findRootOfBottinFiche($fiche);
            $source = 'local';
            if (in_array($root, [Bottin::COMMERCES, Bottin::SANTECO])) {
                $source = 'https://cap.marche.be';
            }
            $documents[] = Document::documentFromFiche($fiche, $idWpSite, $source);
        }

        $data = [];
        foreach ($documents as $document) {
            $skip = false;
            foreach ($document->tags as $category) {
                if (in_array($category['id'], $this->skips)) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) {
                $data[] = $document;
            }
        }

        return $data;
    }

    /**
     * @return array<int,Document>
     */
    public function indexCategoriesBottin(): array
    {
        $documents = [];
        $data = $this->getAllCategoriesBottin();
        foreach ($data as $document) {
            if (in_array($document->id, $this->skips)) {
                continue;
            }
            $id = 'bottin_cat_'.$document->id;
            $document->id = $id;
            $documents[] = $document;
        }

        return $documents;
    }

    /**
     * @return Document[]
     *
     * @throws \Exception
     */
    public function getAllCategoriesBottin(): array
    {
        $data = $this->bottinRepository->getAllCategories();
        $documents = [];
        foreach ($data as $category) {
            $paths = [];
            if ($category->parent_id > 0) {
                $parent = $this->bottinRepository->getCategory($category->parent_id);
                if ($parent) {
                    $paths[] = ['id' => $parent->id, 'name' => $parent->name];
                    $parent2 = $this->bottinRepository->getCategory($category->parent_id);
                    if ($parent2) {
                        $paths[] = ['id' => $parent2->id, 'name' => $parent2->name];
                    }
                }
            }
            $category->tags = [];
            $category->paths = $paths;
            $documents[] = Document::documentFromCategoryBottin($category, 'https://cap.marche.be');
        }

        return $documents;
    }

    /**
     * @return Document[]
     */
    public function getEnqueteDocuments(): array
    {
        $apiRepository = new ApiRepository();
        $enquetes = $apiRepository->getEnquetesPubliques();
        $category = get_category(Theme::ENQUETE_DIRECTORY_URBA);
        $paths = [];
        $documents = [];
        if ($category) {
            $paths = [['id' => $category->term_id, 'name' => $category->name]];
        }
        foreach ($enquetes as $enquete) {
            $enquete->paths = $paths;
            $documents[] = Document::documentFromEnquete($enquete, 'Enquêtes publiques');
        }

        return $documents;

    }

    /**
     * @return Document[]
     */
    public function getAllPublications(): array
    {
        $apiRepository = new ApiRepository();
        $publications = $apiRepository->getAllPublications();
        $documents = [];
        foreach ($publications as $publication) {
            $category = get_category($publication->category->wpCategoryId);
            $publication->paths = [];
            if ($category) {
                $publication->paths = [['id' => $category->term_id, 'name' => $category->name]];
            }
            $documents[] = Document::documentFromPublication($publication, 'Publications communales');
        }

        return $documents;
    }
}