<?php

namespace AcMarche\Theme\Repository;

use AcMarche\Theme\Inc\Theme;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiRepository
{
    private HttpClientInterface $client;

    public function __construct(?HttpClientInterface $client = null)
    {
        $this->client = $client ?? HttpClient::create();
    }

    /**
     * @param int|null $categoryId categorie WordPress (Theme::ENQUETE_DIRECTORY_*), null pour toutes
     * @return array<int,\stdClass>
     */
    public function getEnquetesPubliques(?int $categoryId = null): array
    {
        $enquetes = $this->fetchEnquetesPubliques();
        if ($categoryId === null) {
            return $enquetes;
        }

        return array_values(
            array_filter(
                $enquetes,
                fn(\stdClass $enquete) => (int)($enquete->categoriewp ?? 0) === $categoryId
            )
        );
    }

    /**
     * @return array<int,\stdClass>
     */
    private function fetchEnquetesPubliques(): array
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://extranet.marche.be/enquete/api/'
            );
        } catch (TransportExceptionInterface $e) {
            return [];
        }
        try {
            $content = $response->getContent();
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return [];
        }

        try {
            return json_decode($content, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return [];
        }
    }

    public function getEnquetePublique(int $enqueteId): ?\stdClass
    {
        $enquetes = $this->getEnquetesPubliques();
        foreach ($enquetes as $enquete) {
            if ($enquete->id == $enqueteId) {
                return $enquete;
            }
        }

        return null;
    }

    public function getCategoryEnquete(?\stdClass $enquete = null): ?\WP_Term
    {
        $categoryId = (int)($enquete?->categoriewp ?? 0) ?: Theme::ENQUETE_DIRECTORY_URBA;
        $category = get_category($categoryId);

        return $category instanceof \WP_Term ? $category : null;
    }

}