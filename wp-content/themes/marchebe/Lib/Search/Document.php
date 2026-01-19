<?php

namespace AcMarche\Theme\Lib\Search;

use AcMarche\Theme\Inc\RouterBottin;
use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Bottin\Bottin;
use AcMarche\Theme\Repository\BottinRepository;

class Document
{
    public string $id;
    public string $name;
    public ?string $excerpt = null;
    public string $content;
    public array $tags = [];
    public string $date;
    public string $link;
    public string $type;
    public string $source;
    public int $count = 0;
    public array $site = [];
    private ?string $latitude = null;
    private ?string $longitude = null;

    public static function documentFromPost(\WP_Post|\stdClass $post, int $idSite, string $source): Document
    {
        list($date, $time) = explode(" ", $post->post_date);

        $nameSite = Theme::getTitleBlog($idSite);
        $document = new Document();
        $document->id = self::createId($post->ID ?? $post->id, "post", $idSite);
        $document->name = Cleaner::cleandata($post->post_title);
        $document->source = $source;
        $document->excerpt = Cleaner::cleandata($post->post_excerpt);
        $document->content = Cleaner::cleandata($post->content);
        $document->site = ['name' => $nameSite, 'id' => $idSite];
        $document->tags = $post->tags;
        $document->date = $date;
        $document->type = 'article';
        $document->link = $post->link;

        return $document;
    }

    public static function documentFromCategory(\WP_Term|\stdClass $category, int $idSite, string $source): Document
    {
        $document = new Document();
        $nameSite = Theme::getTitleBlog($idSite);
        $document->id = self::createId($category->term_id ?? $category->id, "category", $idSite);
        $document->name = Cleaner::cleandata($category->name);
        $document->source = $source;
        $document->excerpt = $category->description;
        $document->content = $category->content;
        $document->tags = $category->tags;
        $document->site = ['name' => $nameSite, 'id' => $idSite];
        $document->date = date('Y-m-d');
        $document->type = 'category';
        $document->link = $category->link;

        return $document;
    }

    public static function documentFromFiche(\stdClass $fiche, int $idWpSite, string $source): Document
    {
        $categories = DataForSearch::getCategoriesFiche($fiche);

        $document = new Document();
        $nameSite = Theme::getTitleBlog($idWpSite);
        $document->id = self::createId($fiche->id, "fiche", $idWpSite);
        $document->name = Cleaner::cleandata($fiche->societe);
        $document->source = $source;
        $document->excerpt = Bottin::getExcerpt($fiche);
        $document->content = DataForSearch::getContentFiche($fiche);
        $document->site = ['name' => $nameSite, 'id' => $idWpSite];
        $document->tags = $categories;
        list($date, $heure) = explode(' ', $fiche->created_at);
        $document->date = $date;
        $document->type = 'fiche';
        $document->link = RouterBottin::getUrlFicheBottin($idWpSite, $fiche);
        $document->latitude = $fiche->latitude;
        $document->longitude = $fiche->longitude;

        return $document;
    }

    public static function documentFromCategoryBottin(\stdClass $category, string $source): Document
    {
        $created = explode(' ', $category->created_at);
        $document = new Document();
        $document->id = self::createId($category->id, "category-bottin", Theme::ECONOMIE);
        $document->name = $category->name;
        $document->source = $source;
        $document->excerpt = $category->description;
        $document->tags = $category->tags;
        $document->date = $created[0];
        $document->type = 'category';
        $document->link = RouterBottin::getUrlCategoryBottin($category);
        $fiches = BottinRepository::instanceBottinRepository()->getFichesByCategory($category->id);
        $document->count = count($fiches);
        $document->content = DataForSearch::getContentForCategory($fiches);

        return $document;

    }

    public static function documentFromEnquete(\stdClass $enquete, string $source): Document
    {
        $categories = [];
        $document = new Document();
        $nameSite = Theme::getTitleBlog(Theme::ADMINISTRATION);
        $document->id = self::createId($enquete->id, "enquete", Theme::ADMINISTRATION);
        $document->name = Cleaner::cleandata($enquete->intitule);
        $document->source = $source;
        $document->excerpt = $enquete->rue." ".$enquete->localite;
        $document->content = $enquete->demandeur." ".$enquete->localite." ".$enquete->rue;
        $document->site = ['name' => $nameSite, 'id' => Theme::ADMINISTRATION];
        $document->tags = $categories;
        $document->date = $enquete->date_debut;
        $document->type = 'enquete';
        $document->link = get_category_link(Theme::ENQUETE_DIRECTORY_URBA).'enquete/'.$enquete->id;
        $document->latitude = $enquete->latitude;
        $document->longitude = $enquete->longitude;

        return $document;
    }

    public static function documentFromPublication(\stdClass $item, string $source): Document
    {
        $document = new Document();
        $nameSite = Theme::getTitleBlog(Theme::ADMINISTRATION);
        $document->id = self::createId($item->id, "publication", Theme::ADMINISTRATION);
        $document->name = Cleaner::cleandata($item->title);
        $document->source = $source;
        $document->excerpt = "";
        $document->content = "";
        $document->site = ['name' => $nameSite, 'id' => Theme::ADMINISTRATION];
        $document->tags = [];
        $document->date = $item->createdAt;
        $document->type = 'publication';
        $document->link = $item->url;

        return $document;
    }

    public static function createId(int $id, string $type, ?int $siteId = 0): string
    {
        $id = $type.'-'.$id;
        if ($siteId) {
            $id .= '-'.$siteId;
        }

        return $id;
    }
}