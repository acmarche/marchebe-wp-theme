<?php

namespace AcMarche\Theme\Lib\Helper;

use AcMarche\Theme\Lib\Search\Document;

class SortingHelper
{
    /**
     * @param array<int,Document> $documents
     * @return array<int,Document>
     */
    public static function sortDocuments(array $documents): array
    {
        usort(
            $documents,
            function ($postA, $postB) {
                {
                    if ($postA->name == $postB->name) {
                        return 0;
                    }

                    return ($postA->name < $postB->name) ? -1 : 1;
                }
            }
        );

        return $documents;
    }
}