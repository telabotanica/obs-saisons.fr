<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EspeceRepository")
 */
class Espece
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom_vernaculaire;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom_scientifique;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TypeEspece")
     * @ORM\JoinColumn(nullable=false)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_active;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomVernaculaire(): ?string
    {
        return $this->nom_vernaculaire;
    }

    public function setNomVernaculaire(string $nom_vernaculaire): self
    {
        $this->nom_vernaculaire = $nom_vernaculaire;

        return $this;
    }

    public function getNomScientifique(): ?string
    {
        return $this->nom_scientifique;
    }

    public function setNomScientifique(string $nom_scientifique): self
    {
        $this->nom_scientifique = $nom_scientifique;

        return $this;
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

    public function getType(): ?TypeEspece
    {
        return $this->type;
    }

    public function setType(?TypeEspece $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

}
