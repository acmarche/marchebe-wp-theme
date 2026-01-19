<?php

namespace AcMarche\Theme\Lib\Pivot\Entity;

class EventDate
{
    public ?\DateTimeInterface $dateBegin = null;
    public ?\DateTimeInterface $dateEnd = null;
    public ?\DateTimeInterface $dateRealBegin = null;
    public string $ouvertureHeure1 = '';
    public string $fermetureHeure1 = '';
    public string $ouvertureHeure2 = '';
    public string $fermetureHeure2 = '';
    public string $ouvertureDetails = '';
    public string $dateRange = '';

    public function isSameDate(): bool
    {
        return $this->dateBegin?->format('Y-m-d') === $this->dateEnd?->format('Y-m-d');
    }
}