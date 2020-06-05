<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventSpeciesRepository")
 */
class EventSpecies
{
    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="App\Entity\Event")
     * @ORM\JoinColumn(nullable=false)
     */
    private $event;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="App\Entity\Species")
     * @ORM\JoinColumn(nullable=false)
     */
    private $species;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\column(type="integer", nullable=true)
     */
    private $percentile_5;

    /**
     * @ORM\column(type="integer", nullable=true)
     */
    private $percentile_95;

    /**
     * @ORM\column(type="integer", nullable=true)
     */
    private $percentile_25;

    /**
     * @ORM\column(type="integer", nullable=true)
     */
    private $percentile_75;

    /**
     * @ORM\column(type="integer", nullable=true)
     */
    private $aberration_start_day;

    /**
     * @ORM\column(type="integer", nullable=true)
     */
    private $aberration_end_day;

    const CONIFEROUS_ES_IDS = [
        'species' => [13, 24, 37],
        'events' => [1, 2, 3, 4, 5],
    ];
    const ONLY_FLOWERING_N_FRUITING_ES_IDS = [
        'species' => [16, 40],
        'events' => [3, 4, 5],
    ];

    public function __construct(Event $event, Species $species)
    {
        $this->event = $event;
        $this->species = $species;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function getSpecies(): ?Species
    {
        return $this->species;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPercentile5(): ?int
    {
        return $this->percentile_5;
    }

    public function setPercentile5(?int $percentile_5): self
    {
        $this->percentile_5 = $percentile_5;

        return $this;
    }

    public function getPercentile95(): ?int
    {
        return $this->percentile_95;
    }

    public function setPercentile95(?int $percentile_95): self
    {
        $this->percentile_95 = $percentile_95;

        return $this;
    }

    public function getPercentile25(): ?int
    {
        return $this->percentile_25;
    }

    public function setPercentile25(?int $percentile_25): self
    {
        $this->percentile_25 = $percentile_25;

        return $this;
    }

    public function getPercentile75(): ?int
    {
        return $this->percentile_75;
    }

    public function setPercentile75(?int $percentile_75): self
    {
        $this->percentile_75 = $percentile_75;

        return $this;
    }

    public function getAberrationStartDay(): ?int
    {
        return $this->aberration_start_day;
    }

    public function setAberrationStartDay(?int $aberration_start_day): self
    {
        $this->aberration_start_day = $aberration_start_day;

        return $this;
    }

    public function getAberrationEndDay(): ?int
    {
        return $this->aberration_end_day;
    }

    public function setAberrationEndDay(?int $aberration_end_day): self
    {
        $this->aberration_end_day = $aberration_end_day;

        return $this;
    }
}
