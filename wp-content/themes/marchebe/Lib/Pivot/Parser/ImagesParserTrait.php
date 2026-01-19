<?php

namespace AcMarche\Theme\Lib\Pivot\Parser;

use AcMarche\Theme\Lib\Pivot\Entity\Document;
use AcMarche\Theme\Lib\Pivot\Entity\Event;
use AcMarche\Theme\Lib\Pivot\Entity\RelOffre;
use AcMarche\Theme\Lib\Pivot\Enums\UrnEnum;
use Symfony\Component\String\UnicodeString;

trait ImagesParserTrait
{
    public function parseImages(Event $event): array
    {
        $docs = ['images' => [], 'documents' => []];
        foreach ($event->relOffre as $relatedOffer) {
            if (!in_array(
                $relatedOffer->urn,
                [UrnEnum::MEDIAS_PARTIAL->value, UrnEnum::MEDIA_DEFAULT->value, UrnEnum::MEDIAS_AUTRE->value]
            )) {
                continue;
            }

            if (!$relatedOffer instanceof RelOffre) {
                continue;
            }

            foreach ($relatedOffer->offre->spec as $specData) {
                if ($specData->urn == UrnEnum::URL->value) {
                    $value = str_replace("http:", "https:", $specData->value);
                    $string = new UnicodeString($value);
                    $extension = $string->slice(-3);
                    $document = new Document();
                    $document->extension = $extension->toString();
                    $document->url = $value;
                    $document->codeCgt = $relatedOffer->offre->codeCgt;
                    $document->urn = $relatedOffer->urn;
                    if (in_array($extension, ['jpg', 'png'])) {
                        if (!array_search($value, $docs['images'])) {
                            $docs['images'][] = $value;
                        }
                    } else {
                        $docs['documents'][] = $document;
                    }
                }
            }
        }

        $event->images = $docs['images'];
        $event->documents = $docs['documents'];

        return $docs;
    }
}