<?php

namespace AcMarche\Theme\Lib\Pivot\Parser;

use AcMarche\Theme\Lib\Pivot\Entity\Adresse;
use AcMarche\Theme\Lib\Pivot\Entity\Communication;
use AcMarche\Theme\Lib\Pivot\Entity\Event;
use AcMarche\Theme\Lib\Pivot\Entity\RelOffre;
use AcMarche\Theme\Lib\Pivot\Entity\Spec;
use AcMarche\Theme\Lib\Pivot\Entity\TypeOffre;
use AcMarche\Theme\Lib\Pivot\Entity\User;
use AcMarche\Theme\Lib\Pivot\Entity\UserGlobal;
use AcMarche\Theme\Lib\Pivot\Enums\TypeEnum;
use AcMarche\Theme\Lib\Pivot\Enums\UrnEnum;

class EventParser
{
    use DatesParserTrait, ImagesParserTrait;

    /**
     * @param string $jsonContent
     * @return array<Event>
     * @throws \JsonException|\Throwable
     */
    public function parseJsonFile(string $jsonContent): array
    {
        try {
            $data = json_decode($jsonContent, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \JsonException($e->getMessage());
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON: '.json_last_error_msg());
        }

        $events = [];
        foreach ($data['offre'] as $item) {
            if (isset($item['typeOffre']['idTypeOffre']) && $item['typeOffre']['idTypeOffre'] === TypeEnum::Event->value) {
                $events[] = $this->parseEvent($item);
            }
        }

        return $events;
    }

    public function parseEvent(array $data): Event
    {
        $event = new Event(
            codeCgt: $data['codeCgt'],
            dateCreation: $data['dateCreation'],
            dateModification: $data['dateModification'],
            nom: $data['nom'],
            estActive: $data['estActive'] ?? 0,
            estActiveUrn: $data['estActiveUrn'] ?? [],
            visibilite: $data['visibilite'] ?? 0,
            visibiliteUrn: $data['visibiliteUrn'] ?? [],
            typeOffre: $this->parseTypeOffre($data['typeOffre']),
            adresse1: $this->parseAdresse($data['adresse1'] ?? null),
            spec: array_map(fn($s) => $this->parseSpec($s), $data['spec'] ?? []),
            relOffre: array_map(fn($r) => $this->parseRelOffre($r), $data['relOffre'] ?? []),
            relOffreTgt: $data['relOffreTgt'] ?? [],
        );

        if ($event->typeOffre->idTypeOffre === TypeEnum::Event->value) {
            $this->parseDates($event);
        }

        $this->parseDescription($event);
        $this->parseImages($event);
        $this->parseCommunication($event);

        return $event;
    }

    //removed for security
    private function parseUser(array $data): User
    {
        return new User(
            codeCgt: $data['codeCgt'],
            login: $data['login'],
            nom: $data['nom'],
            prenom: $data['prenom']
        );
    }

    //removed for security
    private function parseUserGlobal(array $data): UserGlobal
    {
        return new UserGlobal(
            idUserglobal: $data['idUserglobal'],
            nom: $data['nom']
        );
    }

    private function parseTypeOffre(array $data): TypeOffre
    {
        return new TypeOffre(
            idTypeOffre: $data['idTypeOffre'],
            label: $data['label'] ?? null
        );
    }

    private function parseAdresse(array|null $data): ?Adresse
    {
        if (!$data) {
            return null;
        }

        return new Adresse(
            rue: $data['rue'] ?? null,
            numero: $data['numero'] ?? null,
            idIns: $data['idIns'] ?? null,
            ins: $data['ins'] ?? null,
            cp: $data['cp'] ?? null,
            localite: $data['localite'] ?? null,
            commune: $data['commune'] ?? null,
            lieuDit: $data['lieuDit'] ?? null,
            province: $data['province'] ?? null,
            provinceUrn: $data['provinceUrn'] ?? null,
            pays: $data['pays'] ?? null,
            paysUrn: $data['paysUrn'] ?? null,
            lambertX: $data['lambertX'] ?? null,
            lambertY: $data['lambertY'] ?? null,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            altitude: $data['altitude'] ?? null,
            noaddress: $data['noaddress'] ?? null,
            parcNaturel: $data['parcNaturel'] ?? null,
            organisme: $data['organisme'] ?? null
        );
    }

    private function parseSpec(array $data): Spec
    {
        return new Spec(
            urn: $data['urn'],
            urnCat: $data['urnCat'] ?? null,
            urnCatLabel: $data['urnCatLabel'] ?? null,
            urnSubCat: $data['urnSubCat'] ?? null,
            urnSubCatLabel: $data['urnSubCatLabel'] ?? null,
            order: $data['order'] ?? null,
            type: $data['type'] ?? null,
            label: $data['label'] ?? null,
            value: $data['value'] ?? null,
            valueLabel: $data['valueLabel'] ?? null,
            spec: $data['spec'] ?? []
        );
    }

    /**
     * urn:cat:descmarket
     * @param Event $event
     * @return void
     */
    private function parseDescription(Event $event): void
    {
        $event->description = $this->findByUrn($event, UrnEnum::DESCRIPTION->value);
    }

    /**
     * urn:cat:moycom
     * @param Event $event
     * @return void
     */
    private function parseCommunication(Event $event): void
    {
        $communication = new Communication();
        $communication->facebook = $this->findByUrn($event, UrnEnum::FACEBOOK->value);
        $communication->mail1 = $this->findByUrn($event, UrnEnum::MAIL1->value);
        $communication->mail2 = $this->findByUrn($event, UrnEnum::MAIL2->value);
        $communication->phone1 = $this->findByUrn($event, UrnEnum::PHONE1->value);
        $communication->phone2 = $this->findByUrn($event, UrnEnum::PHONE2->value);
        $communication->mobile1 = $this->findByUrn($event, UrnEnum::MOBI1->value);
        $communication->mobile2 = $this->findByUrn($event, UrnEnum::MOBI2->value);
        $communication->website = $this->findByUrn($event, UrnEnum::WEB->value);
        $communication->pinterest = $this->findByUrn($event, UrnEnum::PINTEREST->value);
        $communication->youtube = $this->findByUrn($event, UrnEnum::YOUTUBE->value);
        $communication->flickr = $this->findByUrn($event, UrnEnum::FLICKR->value);
        $communication->instagram = $this->findByUrn($event, UrnEnum::INSTAGRAM->value);

        $event->communication = $communication;
    }

    private function parseRelOffre(array $data): RelOffre
    {
        $relOffre = new RelOffre(
            urn: $data['urn'],
            label: $data['label'] ?? 'null',
        );
        if ($data['offre']) {
            $relOffre->offre = $this->parseEvent($data['offre']);
        }

        return $relOffre;
    }

    public function findByUrn(Event $event, string $urnName, bool $returnValue = true): Spec|string|null
    {
        foreach ($event->spec as $specification) {
            if ($specification->urn === $urnName) {
                if ($returnValue) {
                    return $specification->value;
                }

                return $specification;
            }
        }

        return null;
    }
}