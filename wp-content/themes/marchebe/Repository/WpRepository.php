<?php

namespace AcMarche\Theme\Repository;

use AcMarche\Theme\Inc\BottinCategoryMetaBox;
use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Helper\SortingHelper;
use AcMarche\Theme\Lib\Search\Document;
use AcMarche\Theme\Lib\Sort\AcSort;
use WP_Post;
use WP_Query;

class WpRepository
{
    private static ?WpRepository $instance = null;

    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param int $max
     *
     * @return WP_Post[]
     */
    public static function getNews(int $max = 50): array
    {
        $news = array();

        foreach (Theme::SITES as $idSite => $name) :
            switch_to_blog($idSite);

            $args = array(
                'category_name' => 'actualites-principales',
                'orderby' => 'title',
                'post_status' => 'publish',
                'order' => 'ASC',
            );

            if ($idSite == 1) {
                $args = array(
                    'category_name' => 'actualites',
                    'orderby' => 'title',
                    'post_status' => 'publish',
                    'order' => 'ASC',
                );
            }

            $querynews = new WP_Query($args);

            while ($querynews->have_posts()) :

                $post = $querynews->next_post();
                $id = $post->ID;

                if (has_post_thumbnail($id)) {
                    $attachment_id = get_post_thumbnail_id($id);
                    $post->post_thumbnail_id = $attachment_id;
                    $post->post_thumbnail_url = wp_get_attachment_image_url($attachment_id, 'news-thumbnail');
                    $post->post_thumbnail_srcset = wp_get_attachment_image_srcset($attachment_id, 'news-thumbnail');
                    $post->post_thumbnail_sizes = wp_get_attachment_image_sizes($attachment_id, 'news-thumbnail');
                } else {
                    $post->post_thumbnail_url = get_template_directory_uri().'/assets/images/404.jpg';
                }

                $permalink = get_permalink($id);
                $post->link = $permalink;

                $post->blog_id = $idSite;
                $post->blog = ucfirst($name);
                $post->color = Theme::COLORS[$idSite];
                $post->colorTailwind = 'text-'.Theme::SITES[$idSite];

                $news[] = $post;
            endwhile;
        endforeach;

        switch_to_blog(1);
        wp_reset_postdata();

        $news = AcSort::trieNews($news);

        if (count($news) > $max) {
            $news = array_slice($news, 0, $max);
        }

        return $news;
    }

    /**
     * @param int $categoryIdSelected
     * @return array<int,Document>
     */
    public function getPostsAndFiches(int $categoryIdSelected): array
    {
        $documents = [];
        $currentSite = get_current_blog_id();
        $posts = $this->getPostsByCategory($categoryIdSelected);
        foreach ($posts as $post) {
            $this->preparePost($post);
            $document = Document::documentFromPost($post, $currentSite, 'local');
            $documents[] = $document;
        }

        $categoryBottinId = get_term_meta($categoryIdSelected, BottinCategoryMetaBox::KEY_NAME, true);

        if ($categoryBottinId) {
            $bottinRepository = new BottinRepository();
            $fiches = $bottinRepository->getFichesByCategory($categoryBottinId);

            foreach ($fiches as $fiche) {
                $idSite = $bottinRepository->findByFicheIdWpSite($fiche);
                $documents[] = Document::documentFromFiche($fiche, $idSite,'bottin');
            }
        }

        if ($currentSite === Theme::ADMINISTRATION && $categoryIdSelected === Theme::ENQUETE_DIRECTORY_URBA) {
            $apiRepository = new ApiRepository();
            $enquetes = $apiRepository->getEnquetesPubliques();
            foreach ($enquetes as $enquete) {
                $enquete->paths = [];
                $documents[] = Document::documentFromEnquete($enquete,'Enquêtes publiques');
            }
        }

        if ($currentSite === Theme::ADMINISTRATION) {
            $publications = ApiRepository::getPublicationsByCategoryWp($categoryIdSelected);
            foreach ($publications as $item) {
                $item->paths = [];
                $documents[] = Document::documentFromPublication($item,'Publications communales');
            }
        }

        return SortingHelper::sortDocuments($documents);
    }

    public function preparePost(WP_Post $post): void
    {
        $categories = [];
        foreach (get_the_category($post->ID) as $category) {
            $categories[] = ['id' => $category->term_id, 'name' => $category->name];
        }
        $post->tags = $categories;
        $content = get_the_content(null, null, $post);
        $post->content = apply_filters('the_content', $content);
        $post->link = get_permalink($post);
    }

    /**
     * @param int $categoryId
     * @return array<int,WP_Post>
     */
    public function getPostsByCategory(int $categoryId): array
    {
        $args = array(
            'cat' => $categoryId,
            'numberposts' => 5000,
            'orderby' => 'post_title',
            'order' => 'ASC',
            'post_status' => 'publish',
        );

        $querynews = new WP_Query($args);
        $posts = [];
        while ($querynews->have_posts()) {
            $post = $querynews->next_post();
            $posts[] = $post;
        }

        return $posts;
    }

    /**
     * @param int $categoryId
     *
     * @return \WP_Term|\WP_Error|array|null
     */
    public function getParentCategory(int $categoryId): \WP_Term|\WP_Error|array|null
    {
        $category = get_category($categoryId);

        if ($category) {
            if ($category->parent < 1) {
                return null;
            }

            return get_category($category->parent);
        }

        return null;

    }

    /**
     * @param int $cat_ID The ID of the parent category.
     * @return array<int,\WP_Term> Array of child categories with additional properties like URL and ID.
     */
    public function getChildrenOfCategory(int $cat_ID): array
    {
        $args = ['parent' => $cat_ID, 'hide_empty' => false];
        $children = get_categories($args);
        array_map(
            function ($category) {
                $category->url = get_category_link($category->term_id);
                $category->id = $category->term_id;
            },
            $children
        );

        return $children;
    }


    /**
     * @return array<int,\WP_Term> Array of child categories with additional properties like URL and ID.
     */
    public function getRootCategories(): array
    {
        $children = get_categories([
            'parent' => 0,
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false,
        ]);
        array_map(
            function ($category) {
                $category->url = get_category_link($category->term_id);
                $category->id = $category->term_id;
            },
            $children
        );

        return $children;
    }

    /**
     * @param int $categoryId
     * @return array<int,\WP_Term>
     */
    public function getAncestorsOfCategory(int $categoryId): array
    {
        $ancestors = get_ancestors($categoryId, 'category');
        $parents = [];
        if (is_iterable($ancestors)) {
            foreach (array_reverse($ancestors) as $id) {
                $categoryParent = get_category($id);
                $parents[] = $categoryParent;
            }
        }

        return $parents;
    }

    /**
     * @param int $postId
     * @return array<int,\WP_Term>
     */
    public function getAncestorsOfPost(int $postId): array
    {
        $categories = get_the_category($postId);
        if (count($categories) > 0) {
            $firstCategory = $categories[0];
            $ancestors = $this->getAncestorsOfCategory($firstCategory->term_id);
            $ancestors[] = $firstCategory;

            return $ancestors;
        }

        return [];
    }

}