<?php

namespace AcMarche\Theme\Repository;

use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\Search\Cleaner;
use AcMarche\Theme\Lib\Search\Document;
use Exception;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AdlRepository
{
    private HttpClientInterface $client;

    public function __construct()
    {
        $this->client = HttpClient::createForBaseUri($_ENV['ADL_URL']);
    }

    /**
     * @return array<int,Document>
     * @throws Exception
     */
    public function getAllPosts(): array
    {
        $dataString = $this->executeRequest('/posts/?per_page=100');
        $posts = json_decode($dataString);
        $documents = [];
        foreach ($posts as $post) {
            $this->preparePost($post);
            $documents[] = Document::documentFromPost($post, Theme::ECONOMIE, 'https://adl.marche.be');
        }

        return $documents;
    }

    /**
     * @return array<int,Document>
     * @throws Exception
     */
    public function getAllCategories(): array
    {
        $dataString = $this->executeRequest('/categories');
        $categories = json_decode($dataString);
        $today = new \DateTime();
        $date = $today->format('Y-m-d');
        $data = [];
        foreach ($categories as $category) {
            $description = '';
            if ($category->description) {
                $description = Cleaner::cleandata($category->description);
            }

            $content = $description;

            foreach ($this->getPostsByCategoryId($category->id) as $documentElastic) {
                $content .= $documentElastic->name;
                $content .= $documentElastic->excerpt;
                $content .= $documentElastic->content;
            }
            $category->description = $description;
            $category->content = $content;
            $category->created_at = $date;
            $category->tags = [];

            $data[] = Document::documentFromCategory($category, Theme::ECONOMIE,'https://adl.marche.be');
        }

        return $data;
    }

    /**
     * @param int $categoryId
     * @return Document[]
     * @throws Exception
     */
    private function getPostsByCategoryId(int $categoryId): array
    {
        $dataString = $this->executeRequest('/posts/?categories='.$categoryId);

        $posts = json_decode($dataString);
        $data = [];

        foreach ($posts as $post) {
            $this->preparePost($post);
            $data[] = Document::documentFromPost($post, Theme::ECONOMIE, 'https://adl.marche.be');
        }

        return $data;
    }

    /**
     * @param \stdClass $post
     * @return void
     */
    private function preparePost(\stdClass $post): void
    {
        $categories = [];
        foreach ($post->categories as $categoryId) {
            $categories[] = ['id' => $categoryId, 'name' => ''];
        }
        $post->tags = $categories;
        $post->content = $post->content->rendered;
        $post->post_title = Cleaner::cleandata($post->title->rendered);
        $post->post_excerpt = Cleaner::cleandata($post->excerpt->rendered);
        $today = new \DateTime();
        $post->post_date = $today->format('Y-m-d')." ";
        $post->paths = [];
    }

    /**
     * @throws Exception
     */
    private function executeRequest(string $url, array $options = [], string $method = 'GET'): string
    {
        try {
            $response = $this->client->request(
                $method,
                '/wp-json/wp/v2'.$url,
                $options
            );

            return $response->getContent();
        } catch (ClientException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception) {
            throw  new Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

}