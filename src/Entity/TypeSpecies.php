<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TypeSpeciesRepository")
 */
class TypeSpecies
{
    const REIGN = ['animaux', 'plantes'];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=7)
     */
    private $reign;

    const PLURAL_TYPES = [
        'arbre' => 'arbres',
        'herbacée' => 'herbacées',
        'oiseau' => 'oiseaux',
        'insecte' => 'insectes',
        'amphibien' => 'amphibiens',
        'reptile' => 'reptiles',
    ];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getReign(): ?string
    {
        return $this->reign;
    }

    public function setReign(string $reign): self
    {
        if (!in_array($reign, self::REIGN)) {
            throw new \InvalidArgumentException("\"Reign\" invalide (valeurs possibles: \"animaux\", \"plantes\")");
        }
        $this->reign = $reign;

        return $this;
    }
}
