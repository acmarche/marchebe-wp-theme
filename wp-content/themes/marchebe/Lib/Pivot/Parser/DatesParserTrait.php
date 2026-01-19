<?php

namespace AcMarche\Theme\Lib\Pivot\Parser;

use AcMarche\Theme\Lib\Pivot\Entity\Event;
use AcMarche\Theme\Lib\Pivot\Entity\EventDate;
use AcMarche\Theme\Lib\Pivot\Enums\UrnEnum;
use AcMarche\Theme\Lib\Pivot\Helper\DateHelper;
use AcMarche\Theme\Lib\Pivot\Helper\SortHelper;

trait DatesParserTrait
{
    /**
     *
     * @param Event $event
     * @return void
     */
    public function parseDates(Event $event): void
    {
        //$this->parseDatesValidation($event);
        $today = new \DateTime();
        $allDates = [];
        $spec = $this->findByUrn($event, UrnEnum::DATE_OBJECT->value, returnValue: false);
        if ($spec) {
            $dateEvent = new EventDate();
            foreach ($spec->spec as $row) {
                if ($data = $this->getData($row, UrnEnum::DATE_DEB)) {
                    $dateEvent->dateBegin = DateHelper::convertStringToDateTime($data);
                    $dateEvent->dateRealBegin = $dateEvent->dateBegin;
                    /**
                     * Exception for event on all the year
                     * like st loup or marche public
                     */
                    if ($dateEvent->dateBegin->format('Y-m-d') < $today->format('Y-m-d')) {
                        $dateEvent->dateBegin = $today;
                    }
                }
                if ($data = $this->getData($row, UrnEnum::DATE_END)) {
                    $dateEvent->dateEnd = DateHelper::convertStringToDateTime($data);
                }
                if ($data = $this->getData($row, UrnEnum::DATE_OUVERTURE_HEURE_1)) {
                    $dateEvent->ouvertureHeure1 = $data;
                }
                if ($data = $this->getData($row, UrnEnum::DATE_FERMETURE_HEURE_1)) {
                    $dateEvent->fermetureHeure1 = $data;
                }
                if ($data = $this->getData($row, UrnEnum::DATE_OUVERTURE_HEURE_2)) {
                    $dateEvent->ouvertureHeure2 = $data;
                }
                if ($data = $this->getData($row, UrnEnum::DATE_FERMETURE_HEURE_2)) {
                    $dateEvent->fermetureHeure2 = $data;
                }
                if ($data = $this->getData($row, UrnEnum::DATE_DETAIL_OUVERTURE)) {
                    $dateEvent->ouvertureDetails = $data;
                }
                if ($data = $this->getData($row, UrnEnum::DATE_RANGE)) {
                    $dateEvent->dateRange = $data;
                }
            }
            $allDates[] = $dateEvent;
        }

        $allDates = SortHelper::sortDatesEvent($allDates);
        $event->dates = $allDates;
    }

    /**
     * IN content detail level 4 don't have this data
     * @param Event $offre
     * @return void
     */
    public function parseDatesValidation(Event $offre): void
    {
        $value = $this->findByUrn($offre, UrnEnum::DATE_DEB_VALID->value);
        if ($value) {
            $offre->dateDebValid = DateHelper::convertStringToDateTime($value);
        }
        $value = $this->findByUrn($offre, UrnEnum::DATE_FIN_VALID->value);
        if ($value) {
            $offre->dateFinValid = DateHelper::convertStringToDateTime($value);
        }
    }

    /**
     * Bug server www
     * @param array $data
     * @param UrnEnum $urn
     * @return string|null
     */
    private function getData(array $data, UrnEnum $urn): ?string
    {
        if ($data['urn'] === $urn->value) {
            return $data['value'] ?? null;
        }

        return null;
    }

}