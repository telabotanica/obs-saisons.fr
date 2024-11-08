<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_PENDING = 2;

    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_RELAY = 'ROLE_RELAY';


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\Column(type="smallint")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $displayName;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $seenAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $resetToken;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $avatar;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $locality;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $country;

    /**
     * @Assert\Regex(
     *     pattern = "/(100[1-9]|10[1-9][0-9]|1[1-9][0-9]{2}|[2-9][0-9]{3}|[1-8][0-9]{4}|9[0-8][0-9]{3}|990[0-9]{2}|991[0-2][0-9]|9913[0-8])|(2(A001|B36[4-6]|(A|B)(00[2-9]|0[1-8][0-9]|09[0-9]|[12][0-9]{2}|3[0-5][0-9]|36[0-3])))/",
     *     message = "Le code postal saisi est invalide"
     * )
     *
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $postCode;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $profileType;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    private $isNewsletterSubscriber;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private $emailNew;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $emailToken;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $legacyId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TypeRelays")
     * @ORM\JoinColumn(nullable=true)
     */
    private $typeRelays;

    public function __construct()
    {
        $this->isNewsletterSubscriber = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A unique identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->getEmail();
    }

    /**
     * A unique identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->getEmail();
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return in_array(User::ROLE_ADMIN, $this->getRoles());
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = User::ROLE_USER;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = array_unique($roles);

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getName(): ?string
    {
        if ($this->deletedAt) {
            return 'Utilisateur supprimé';
        }

        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        if ($this->deletedAt) {
            return 'Utilisateur supprimé';
        }

        if (!empty($this->displayName)) {
            return $this->displayName;
        }

        if (!empty($this->getName())) {
            return $this->getName();
        }

        return mb_convert_case(explode('@', $this->getEmail())[0], MB_CASE_TITLE);
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;

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

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getSeenAt(): ?\DateTimeInterface
    {
        return $this->seenAt;
    }

    public function setSeenAt(?\DateTimeInterface $seenAt): self
    {
        $this->seenAt = $seenAt;

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getAvatar(): ?string
    {
        if ($this->deletedAt) {
            return null;
        }

        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function setLocality(?string $locality): self
    {
        $this->locality = $locality;

        return $this;
    }

    public function getLocality(): ?string
    {
        if ($this->deletedAt) {
            return null;
        }

        return $this->locality;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCountry(): ?string
    {
        if ($this->deletedAt) {
            return null;
        }

        return $this->country;
    }

    public function setPostCode(?string $postCode): self
    {
        $this->postCode = $postCode;

        return $this;
    }

    public function getPostCode(): ?string
    {
        if ($this->deletedAt) {
            return null;
        }

        return $this->postCode;
    }

    public function setProfileType(?string $profileType): self
    {
        $this->profileType = $profileType;

        return $this;
    }

    public function getProfileType(): ?string
    {
        if ($this->deletedAt) {
            return null;
        }

        return $this->profileType;
    }

    public function setIsNewsletterSubscriber(?bool $isNewsletterSubscriber): self
    {
        $this->isNewsletterSubscriber = $isNewsletterSubscriber;

        return $this;
    }

    public function getIsNewsletterSubscriber(): ?bool
    {
        return $this->isNewsletterSubscriber;
    }

    public function getEmailNew(): ?string
    {
        return $this->emailNew;
    }

    public function setEmailNew(?string $emailNew): self
    {
        $this->emailNew = mb_convert_case(trim($emailNew), MB_CASE_LOWER);

        return $this;
    }

    public function getEmailToken(): ?string
    {
        return $this->emailToken;
    }

    public function setEmailToken(?string $emailToken): self
    {
        $this->emailToken = $emailToken;

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

    public function getTypeRelays(): ?TypeRelays
    {
        return $this->typeRelays;
    }

    public function setTypeRelays(?TypeRelays $type): self
    {
        $this->typeRelays = $type;

        return $this;
    }


}
