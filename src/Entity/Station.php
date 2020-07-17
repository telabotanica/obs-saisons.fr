<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 * @ORM\Entity(repositoryClass="App\Repository\StationRepository")
 */
class Station
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPrivate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $user;

    /**
     * @Gedmo\Slug(fields={"createdAt", "locality", "name"}, dateFormat="Y/m")
     * @ORM\Column(type="string", length=100, unique=true)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $locality;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $habitat;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $headerImage;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @Assert\Range(
     *      min = 41.3332,
     *      max = 51.0890,
     *      notInRangeMessage = "Vous devez entrer un point en France métropolitaine"
     * )
     *
     * @ORM\Column(type="decimal", precision=8, scale=5)
     */
    private $latitude;

    /**
     * @Assert\Range(
     *      min = -5.17470,
     *      max = 9.56000,
     *      notInRangeMessage = "Vous devez entrer un point en France métropolitaine"
     * )
     *
     * @ORM\Column(type="decimal", precision=8, scale=5)
     */
    private $longitude;

    /**
     * @ORM\Column(type="decimal")
     */
    private $altitude;

    /**
     * @Assert\Regex(
     *     pattern = "/(100[1-9]|10[1-9][0-9]|1[1-9][0-9]{2}|[2-9][0-9]{3}|[1-8][0-9]{4}|9[0-8][0-9]{3}|990[0-9]{2}|991[0-2][0-9]|9913[0-8])|(2(A001|B36[4-6]|(A|B)(00[2-9]|0[1-8][0-9]|09[0-9]|[12][0-9]{2}|3[0-5][0-9]|36[0-3])))/",
     *     message = "Le code INSEE saisi est invalide"
     * )
     *
     * @ORM\Column(type="string", length=5)
     */
    private $inseeCode;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsPrivate(): ?bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): self
    {
        $this->isPrivate = $isPrivate;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getLocality(): ?string
    {
        return $this->locality;
    }

    public function setLocality(string $locality): self
    {
        $this->locality = $locality;

        return $this;
    }

    public function getHabitat(): ?string
    {
        return $this->habitat;
    }

    public function setHabitat(string $habitat): self
    {
        $this->habitat = $habitat;

        return $this;
    }

    public function getHeaderImage(): ?string
    {
        return $this->headerImage;
    }

    public function setHeaderImage(?string $headerImage): self
    {
        $this->headerImage = $headerImage;

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

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function setAltitude(string $altitude): self
    {
        $this->altitude = $altitude;

        return $this;
    }

    public function getAltitude(): ?string
    {
        return $this->altitude;
    }

    public function setInseeCode(string $inseeCode): self
    {
        $this->inseeCode = $inseeCode;

        return $this;
    }

    public function getInseeCode(): ?string
    {
        return $this->inseeCode;
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
}
