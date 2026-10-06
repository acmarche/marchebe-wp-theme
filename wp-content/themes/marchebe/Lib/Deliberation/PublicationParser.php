<?php

namespace AcMarche\Theme\Lib\Deliberation;

use Dom\Element;
use Dom\HTMLDocument;

/**
 * Lit le HTML de deliberations.be (plateforme Plone/iMio, page "publications").
 * Ce n'est pas une API documentee: le parseur reste tolerant et ignore
 * ce qu'il ne reconnait pas plutot que d'echouer.
 */
class PublicationParser
{
    /**
     * Une page de @@faceted_query: 20 cartes ".item-card" au plus.
     * @return array<int, array<string, mixed>>
     */
    public function parseListing(string $html): array
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $publications = [];

        foreach ($document->querySelectorAll('.item-card') as $card) {
            $url = $card->querySelector('a.filled-link')?->getAttribute('href');
            if (!$url) {
                continue;
            }

            $metadata = [];
            foreach ($card->querySelectorAll('.item-metadata-row') as $row) {
                $label = $this->text($row->querySelector('.item-metadata-label'));
                if ($label !== '') {
                    $metadata[$label] = $this->text($row->querySelector('span'));
                }
            }

            $publications[] = [
                'url' => $url,
                'slug' => basename(parse_url($url, PHP_URL_PATH)),
                'title' => $this->text($card->querySelector('.card-title')),
                'description' => $this->text($card->querySelector('#description-row')),
                'date' => $this->parseDate($metadata['Date de publication'] ?? ''),
                'nature' => $metadata['Nature du document'] ?? null,
                'matiere' => $metadata['Matière'] ?? null,
                'metadata' => $metadata,
                'pdf' => null,
            ];
        }

        return $publications;
    }

    /**
     * La pagination affiche un lien "20 éléments suivants" tant qu'il reste des pages.
     */
    public function hasNextPage(string $html): bool
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        foreach ($document->querySelectorAll('a.page-link') as $link) {
            if (str_contains($link->textContent, 'suivant')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Liste des natures du filtre de la page: <select name="nature">, slug => libelle.
     * Le slug sert de cle (meta des categories), le libelle est celui affiche sur les cartes.
     * @return array<string, string>
     */
    public function parseNatures(string $html): array
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $natures = [];

        foreach ($document->querySelectorAll('select[name="nature"] option') as $option) {
            $slug = trim($option->getAttribute('value') ?? '');
            // l'option "Tous" a une valeur vide
            if ($slug !== '') {
                $natures[$slug] = $this->text($option);
            }
        }

        return $natures;
    }

    /**
     * Page de detail: le PDF est dans <x-pdf-viewer file="...">.
     */
    public function parsePdfUrl(string $html): ?string
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $file = $document->querySelector('x-pdf-viewer')?->getAttribute('file');
        if (!$file) {
            return null;
        }

        // le nom du fichier arrive brut (espaces, accents, apostrophes): on encode le dernier segment
        $position = strrpos($file, '/');

        return substr($file, 0, $position + 1).rawurlencode(rawurldecode(substr($file, $position + 1)));
    }

    private function parseDate(string $value): ?string
    {
        $date = \DateTimeImmutable::createFromFormat('!d/m/Y', trim($value));

        return $date ? $date->format('Y-m-d') : null;
    }

    private function text(?Element $element): string
    {
        return $element ? trim(preg_replace('/\s+/u', ' ', $element->textContent)) : '';
    }
}
