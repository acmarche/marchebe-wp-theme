<?php

namespace AcMarche\Theme\Lib\Pivot\Repository;

use AcMarche\Theme\Lib\Cache;
use AcMarche\Theme\Lib\Pivot\Entity\Event;
use AcMarche\Theme\Lib\Pivot\Enums\ContentEnum;
use AcMarche\Theme\Lib\Pivot\Helper\SortHelper;
use AcMarche\Theme\Lib\Pivot\Parser\EventParser;
use Carbon\Carbon;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PivotRepository
{
    private PivotApi $pivotApi;
    //skip marche public, st loup
    private array $eventsToSkip = ['EVT-01-0AVJ-324P', 'EVT-A0-008E-101W'];
    public static string $keyAll = 'all-events-marche-be';
    private EventParser $parser;

    public function __construct()
    {
        $this->pivotApi = new PivotApi();
        $this->parser = new EventParser();
    }

    /**
     * Ces requêtes sont créées et stockées par les opérateurs de PIVOT afin de fournir des flux
     * de données. Les requêtes sont accessibles au moyen d’un code identifiant unique (codeCgt).
     * @throws \Exception
     */
    public function queryContent(int $level = 2): \stdClass
    {
        try {
            $response = $this->pivotApi->query($level);
            $content = $response->getContent();
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw new \Exception($e->getMessage());
        }

        return json_decode($content, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int,Event>
     * @throws \JsonException
     * @throws \Throwable
     */
    public function loadEvents(
        int $level = ContentEnum::LVL4->value,
        bool $purgeCache = false,
        bool $skip = false
    ): array {
        $cacheKey = self::$keyAll;
        if ($purgeCache) {
            Cache::delete($cacheKey);
        }
        $jsonContent = Cache::getIfExists($cacheKey);

        if (!$jsonContent) {
            if (is_readable($filename = $_ENV['APP_CACHE_DIR'].'/../data/pivot.json')) {
                $jsonContent = file_get_contents($filename);
            }
        }

        if (!$jsonContent) {
            return [];
        }

        $parser = new EventParser();
        $events = $parser->parseJsonFile($jsonContent);

        // Remove events with no dates
        $events = array_filter($events, fn(Event $event) => count($event->dates) > 0);

        if ($skip) {
            $all = [];
            foreach ($events as $event) {
                $dateBegin = $event->firstDateBegin();
                $dateEnd = $event->firstDateEnd();
                if ($dateBegin && $dateEnd) {
                    $daysDiff = Carbon::parse($dateBegin)->diffInDays(Carbon::parse($dateEnd));
                    if ($daysDiff > 10) {
                        continue;
                    }
                }
                $all[] = $event;
            }
            $events = $all;
        }

        SortHelper::sortEvents($events);

        return $events;
    }

    /**
     * @param string $codeCgt
     * @param bool $parse
     * @param bool $purgeCache
     * @param int $level
     * @return Event|string|null
     * @throws \JsonException
     */
    public function loadOneEvent(
        string $codeCgt,
        bool $parse = false,
        bool $purgeCache = false,
        int $level = ContentEnum::LVL4->value
    ): Event|string|null {
        $cacheKey = Cache::generateKey(PivotRepository::$keyAll).'-'.$codeCgt;
        if ($purgeCache) {
            Cache::delete($cacheKey);
        }
        $jsonContent = Cache::getIfExists($cacheKey);

        if (!$jsonContent) {
            $filename = $_ENV['APP_CACHE_DIR'].'/../data/pivot.json';

            if (is_readable($filename)) {

                $fileContent = file_get_contents($filename);
                $data = json_decode($fileContent, associative: true, flags: JSON_THROW_ON_ERROR);

                if (isset($data['offre'])) {
                    foreach ($data['offre'] as $offre) {
                        if (isset($offre['codeCgt']) && $offre['codeCgt'] === $codeCgt) {
                            $jsonContent = json_encode($offre, JSON_THROW_ON_ERROR);
                            break;
                        }
                    }
                }
            }
        }

        if (!$jsonContent) {
            return null;
        }

        if (!$parse) {
            return $jsonContent;
        }

        $data = json_decode($jsonContent, associative: true, flags: JSON_THROW_ON_ERROR);

        if (!isset($data['codeCgt'])) {
            if (!isset($data['offre'][0]['codeCgt'])) {
                return null;
            } else {
                $data = $data['offre'][0];
            }
        }

        try {
            $event = $this->parser->parseEvent($data);
            $cacheKey = Cache::generateKey(PivotRepository::$keyAll).'-'.$codeCgt;
            try {
                Cache::get($cacheKey, function () use ($jsonContent) {
                    return $jsonContent;
                });
            } catch (\Exception $e) {
                return null;
            } catch (InvalidArgumentException $e) {
            }

            return $event;
        } catch (\Exception $exception) {
            return null;
        }
    }
}