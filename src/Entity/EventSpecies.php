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
    private $percentile5;

    /**
     * @ORM\column(type="integer", nullable=true)
     */
    private $percentile95;

    /**
     * @ORM\column(type="integer", nullable=true)
     */
    private $percentile25;

    /**
     * @ORM\column(type="integer", nullable=true)
     */
    private $percentile75;

    /**
     * @ORM\column(type="integer", nullable=true)
     */
    private $aberrationStartDay;

    /**
     * @ORM\column(type="integer", nullable=true)
     */
    private $aberrationEndDay;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $featuredStartDay;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $featuredEndDay;

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
        return $this->percentile5;
    }

    public function setPercentile5(?int $percentile5): self
    {
        $this->percentile5 = $percentile5;

        return $this;
    }

    public function getPercentile95(): ?int
    {
        return $this->percentile95;
    }

    public function setPercentile95(?int $percentile95): self
    {
        $this->percentile95 = $percentile95;

        return $this;
    }

    public function getPercentile25(): ?int
    {
        return $this->percentile25;
    }

    public function setPercentile25(?int $percentile25): self
    {
        $this->percentile25 = $percentile25;

        return $this;
    }

    public function getPercentile75(): ?int
    {
        return $this->percentile75;
    }

    public function setPercentile75(?int $percentile75): self
    {
        $this->percentile75 = $percentile75;

        return $this;
    }

    public function getAberrationStartDay(): ?int
    {
        return $this->aberrationStartDay;
    }

    public function setAberrationStartDay(?int $aberrationStartDay): self
    {
        $this->aberrationStartDay = $aberrationStartDay;

        return $this;
    }

    public function getAberrationEndDay(): ?int
    {
        return $this->aberrationEndDay;
    }

    public function setAberrationEndDay(?int $aberrationEndDay): self
    {
        $this->aberrationEndDay = $aberrationEndDay;

        return $this;
    }

    public function getFeaturedStartDay(): ?int
    {
        return $this->featuredStartDay;
    }

    public function setFeaturedStartDay(?int $featuredStartDay): self
    {
        $this->featuredStartDay = $featuredStartDay;

        return $this;
    }

    public function getFeaturedEndDay(): ?int
    {
        return $this->featuredEndDay;
    }

    public function setFeaturedEndDay(?int $featuredEndDay): self
    {
        $this->featuredEndDay = $featuredEndDay;

        return $this;
    }
}
