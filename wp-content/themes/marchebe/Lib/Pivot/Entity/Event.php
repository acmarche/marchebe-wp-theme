<?php

namespace AcMarche\Theme\Lib\Pivot\Entity;

class Event
{
    public User $userCreation;
    public UserGlobal $userGlobalCreation;
    public User $userModification;
    public UserGlobal $userGlobalModification;

    public ?\DateTimeInterface $dateDebValid = null;
    public ?\DateTimeInterface $dateFinValid = null;
    /**
     * @var array<EventDate>
     */
    public array $dates = [];
    /**
     * @var array<string>
     */
    public array $images = [];
    /**
     * @var array<string>
     */
    public array $documents = [];
    public array $tags = [];
    public ?string $description = null;
    public ?Communication $communication = null;

    public function __construct(
        public string $codeCgt,
        public string $dateCreation,
        public string $dateModification,
        public string $nom,
        public int $estActive,
        public array $estActiveUrn,
        public int $visibilite,
        public array $visibiliteUrn,
        public TypeOffre $typeOffre,
        public ?Adresse $adresse1 = null,
        /**
         * @var array<Spec>
         */
        public array $spec,
        /**
         * @var array<RelOffre>
         */
        public array $relOffre,
        public array $relOffreTgt,
    ) {
    }

    public function locality(): ?string
    {
        return $this->adresse1?->getLocalityByLanguage('fr');
    }

    public function firstDate(): ?\DateTimeInterface
    {
        if (count($this->dates) > 0) {
            return $this->dates[0]->dateBegin;
        }

        return null;
    }

    public function firstRealDate(): ?\DateTimeInterface
    {
        if (count($this->dates) > 0) {
            return $this->dates[0]->dateRealBegin;
        }

        return null;
    }

    public function shortCutDateEvent(): array
    {
        return [
            'year' => $this->firstDate()?->format('Y'),
            'month' => $this->firstDate()?->format('m'),
            'day' => $this->firstDate()?->format('d'),
        ];
    }

    public function isEventOnPeriod(): bool
    {
        foreach ($this->dates as $date) {
            if (!$date->isSameDate()) {
                return true;
            }
        }

        return false;
    }

    public function firstImage(): ?string
    {
        if (count($this->images) > 0) {
            return $this->images[0];
        }

        return null;
    }

    public function url(): string
    {
        return '/tourisme/agenda-des-manifestations/manifestation/'.$this->codeCgt;
    }
}