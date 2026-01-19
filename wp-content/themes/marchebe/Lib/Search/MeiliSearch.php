<?php

namespace AcMarche\Theme\Lib\Search;

use Meilisearch\Search\SearchResult;

class MeiliSearch
{
    use MeiliTrait;

    public function __construct()
    {
        $this->indexName = $_ENV['MEILI_INDEX_NAME'] ?? null;
        $this->masterKey = $_ENV['MEILI_MASTER_KEY'] ?? null;
    }

    /**
     * https://www.meilisearch.com/docs/learn/fine_tuning_results/filtering
     * @param string $keyword
     * @param array $filters Example: ['site.id = 1', 'type = "article"']
     * @param int $limit
     * @return SearchResult
     */
    public function doSearch(string $keyword, array $filters = [], int $limit = 100): SearchResult
    {
        $this->initClientAndIndex();

        $options = [
            'limit' => $limit,
            'attributesToHighlight' => ['name', 'excerpt', 'content'],
            'highlightPreTag' => '<mark>',
            'highlightPostTag' => '</mark>',
            'attributesToCrop' => ['content'],
            'cropLength' => 200,
        ];

        if (!empty($filters)) {
            $options['filter'] = $filters;
        }

        return $this->index->search($keyword, $options);
    }
}