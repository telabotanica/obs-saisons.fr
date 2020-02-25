<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 */
class Event
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $stade_bbch;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $description;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_observable;

    const DISPLAY_LABELS = [
        'feuillaison' => 'Feuillaison',
        'floraison' => 'Floraison',
        'fructification' => 'Fructification',
        'sénescence' => 'Sénescence',
        '1ere apparition' => '1ere apparition',
    ];

    const CSS_CLASSES = [
        'Feuillaison' => 'feuillaison',
        'Floraison' => 'floraison',
        'Fructification' => 'fructification',
        'Sénescence' => 'senescence',
        '1ere apparition' => 'apparition',
    ];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStadeBbch(): ?int
    {
        return $this->stade_bbch;
    }

    public function setStadeBbch(?int $stade_bbch): self
    {
        $this->stade_bbch = $stade_bbch;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getIsObservable(): ?bool
    {
        return $this->is_observable;
    }

    public function setIsObservable(?bool $is_observable): self
    {
        $this->is_observable = $is_observable;

        return $this;
    }
}
