<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TypeEspeceRepository")
 */
class TypeEspece
{
    const REIGNE = ['animaux', 'plantes'];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=7)
     */
    private $reigne;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getReigne(): ?string
    {
        return $this->reigne;
    }

    public function setReigne(string $reigne): self
    {
        if (!in_array($reigne, self::REIGNE)) {
            throw new \InvalidArgumentException("\"Reigne\" invalide (valeurs possibles: \"animaux\", \"plantes\")");
        }
        $this->reigne = $reigne;

        return $this;
    }
}
