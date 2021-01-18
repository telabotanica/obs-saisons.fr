<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 *
 * Events hasn't change since first day, (maybe) their details should be in code instead of bdd
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
    private $bbch_code;

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
        '1ère apparition' => '1ère apparition',
    ];

    const CSS_CLASSES = [
        'feuillaison' => 'feuillaison',
        'floraison' => 'floraison',
        'fructification' => 'fructification',
        'sénescence' => 'senescence',
        '1ère apparition' => 'apparition',
    ];

    const ANIMALS_EVENT = '1ère apparition';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStadeBbch(): ?int
    {
        return $this->bbch_code;
    }

    public function setStadeBbch(?int $bbch_code): self
    {
        $this->bbch_code = $bbch_code;

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
