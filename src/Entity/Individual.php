<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 * @ORM\Entity(repositoryClass="App\Repository\IndividualRepository")
 */
class Individual
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
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Species")
     * @ORM\JoinColumn(nullable=false)
     */
    private $species;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Station")
     * @ORM\JoinColumn(nullable=false)
     */
    private $station;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $user;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $details;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $legacyId;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $isDead;

     /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $commentaireMort;

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

    public function getSpecies(): ?Species
    {
        return $this->species;
    }

    public function setSpecies(Species $species): self
    {
        $this->species = $species;

        return $this;
    }

    public function getStation(): ?Station
    {
        return $this->station;
    }

    public function setStation(Station $station): self
    {
        $this->station = $station;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details ?: null;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getLegacyId(): ?int
    {
        return $this->legacyId;
    }

    public function setLegacyId(int $legacyId): self
    {
        $this->legacyId = $legacyId;

        return $this;
    }

    /**
     * Get the value of is_dead
     */ 
    public function getIsDead()
    {
        return $this->isDead;
    }

    /**
     * Set the value of is_dead
     *
     * @return  self
     */ 
    public function setIsDead(?bool $isDead)
    {
        $this->isDead = $isDead;

        return $this;
    }

    /**
     * Get the value of commentaire_mort
     */ 
    public function getCommentaireMort()
    {
        return $this->commentaireMort;
    }

    /**
     * Set the value of commentaire_mort
     *
     * @return  self
     */ 
    public function setCommentaireMort(?string $commentaireMort)
    {
        $this->commentaireMort = $commentaireMort;

        return $this;
    }
}
