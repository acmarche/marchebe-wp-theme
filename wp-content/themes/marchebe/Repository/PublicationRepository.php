<?php

namespace AcMarche\Theme\Repository;

use AcMarche\Theme\Lib\Cache;
use AcMarche\Theme\Lib\Deliberation\PublicationParser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Publications legales (arretes, reglements...) de deliberations.be.
 * Le flux RSS ne donne que les 15 dernieres: l'archive complete passe par
 * @@faceted_query, pagine par 20 (b_size est ignore). Rien avant le 1er juillet 2025.
 */
class PublicationRepository
{
    public const BASE_URL = 'https://www.deliberations.be/marche-en-famenne/publications';
    public static string $keyAll = 'deliberations-publications';
    public static string $keyNatures = 'deliberations-publications-natures';
    // garde-fou si la pagination change de forme et ne s'arrete plus
    private const MAX_PAGES = 100;
    // pause entre deux requetes, le site est derriere Cloudflare
    private const DELAY_MICROSECONDS = 500_000;

    private HttpClientInterface $client;
    private PublicationParser $parser;

    public function __construct(?HttpClientInterface $client = null)
    {
        $this->client = $client ?? HttpClient::create([
            'timeout' => 30,
            'headers' => ['User-Agent' => 'marche.be publications sync'],
        ]);
        $this->parser = new PublicationParser();
    }

    /**
     * Lecture pour le site: ne contacte jamais deliberations.be.
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        return Cache::getIfExists(self::$keyAll) ?? [];
    }

    /**
     * Publications des natures choisies (slugs), pour la page d'une categorie.
     * @param string[] $slugs
     * @return array<int, array<string, mixed>>
     */
    public function findByNatures(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        return array_values(array_filter(
            $this->findAll(),
            fn(array $publication) => in_array($publication['nature_slug'] ?? null, $slugs, true)
        ));
    }

    /**
     * Natures pour l'edition des categories, slug => libelle, triees par libelle.
     * Si le cache des natures manque, on se rabat sur celles des publications en cache.
     * @return array<string, string>
     */
    public function findNatures(): array
    {
        $natures = Cache::getIfExists(self::$keyNatures) ?? [];

        foreach ($this->findAll() as $publication) {
            if (!empty($publication['nature_slug']) && !isset($natures[$publication['nature_slug']])) {
                $natures[$publication['nature_slug']] = $publication['nature'];
            }
        }

        asort($natures, SORT_LOCALE_STRING | SORT_FLAG_CASE);

        return $natures;
    }

    /**
     * Les natures du filtre de la page liste, slug => libelle.
     * @return array<string, string>
     * @throws \Throwable sur erreur HTTP
     */
    public function fetchNatures(): array
    {
        return $this->parser->parseNatures($this->get(self::BASE_URL));
    }

    /**
     * Les cartes ne donnent que le libelle de la nature: on retrouve son slug dans la liste du filtre.
     * @param array<int, array<string, mixed>> $publications
     * @param array<string, string> $natures slug => libelle
     * @return array<int, array<string, mixed>>
     */
    public static function addNatureSlugs(array $publications, array $natures): array
    {
        $slugsByLabel = [];
        foreach ($natures as $slug => $label) {
            $slugsByLabel[self::normalizeLabel($label)] = $slug;
        }

        foreach ($publications as &$publication) {
            $publication['nature_slug'] = $publication['nature']
                ? ($slugsByLabel[self::normalizeLabel($publication['nature'])] ?? null)
                : null;
        }
        unset($publication);

        return $publications;
    }

    /**
     * @param array<string, string> $natures
     */
    public function saveNatures(array $natures): void
    {
        Cache::delete(self::$keyNatures);
        Cache::get(self::$keyNatures, function (ItemInterface $item) use ($natures) {
            // l'edition des categories en depend: on la garde longtemps, chaque synchro la rafraichit
            $item->expiresAfter(60 * 60 * 24 * 365);

            return $natures;
        });
    }

    // apostrophe typographique ou droite, casse: le libelle d'une carte et celui du filtre peuvent differer
    private static function normalizeLabel(string $label): string
    {
        return mb_strtolower(str_replace(['’', '‘'], "'", trim($label)));
    }

    /**
     * Parcourt toutes les pages de la liste.
     * @param callable(int $page, int $count): void|null $onPage
     * @return array<int, array<string, mixed>>
     * @throws \Throwable sur erreur HTTP
     */
    public function fetchAll(?callable $onPage = null): array
    {
        $publications = [];

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            if ($page > 0) {
                usleep(self::DELAY_MICROSECONDS);
            }

            $html = $this->get(self::BASE_URL.'/@@faceted_query', ['b_start:int' => $page * 20]);
            $items = $this->parser->parseListing($html);
            $onPage && $onPage($page + 1, count($items));

            foreach ($items as $item) {
                // une publication peut glisser d'une page a l'autre si une nouvelle arrive pendant le parcours
                $publications[$item['url']] = $item;
            }

            if ($items === [] || !$this->parser->hasNextPage($html)) {
                break;
            }
        }

        $publications = array_values($publications);
        usort($publications, fn(array $a, array $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        return $publications;
    }

    /**
     * Lien du PDF, depuis la page de detail. Un document publie ne change plus:
     * on le garde longtemps en cache pour ne recharger que les nouvelles publications.
     * @throws \Throwable sur erreur HTTP
     */
    public function fetchPdfUrl(string $url, ?bool &$fromCache = null): ?string
    {
        $fromCache = true;

        return Cache::get(self::$keyAll.'-pdf-'.md5($url), function (ItemInterface $item) use ($url, &$fromCache) {
            $fromCache = false;
            $item->expiresAfter(60 * 60 * 24 * 365);
            usleep(self::DELAY_MICROSECONDS);

            return $this->parser->parsePdfUrl($this->get($url));
        });
    }

    /**
     * @param array<int, array<string, mixed>> $publications
     */
    public function save(array $publications): void
    {
        Cache::delete(self::$keyAll);
        Cache::get(self::$keyAll, function (ItemInterface $item) use ($publications) {
            // le site garde la derniere liste connue si la synchro echoue un moment
            $item->expiresAfter(60 * 60 * 24 * 7);

            return $publications;
        });
    }

    private function get(string $url, array $query = []): string
    {
        return $this->client->request('GET', $url, ['query' => $query])->getContent();
    }
}
