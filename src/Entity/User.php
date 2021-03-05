<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity=AuthenticationCode::class, mappedBy="user", orphanRemoval=true)
     */
    private $authenticationCodes;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default":0})
     */
    private $loginAttempt;

    /**
     * @ORM\Column(type="integer")
     */
    private $blocked;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $blockedAt;

    public function __construct()
    {
        $this->authenticationCodes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

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

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|AuthenticationCode[]
     */
    public function getAuthenticationCodes(): Collection
    {
        return $this->authenticationCodes;
    }

    public function addAuthenticationCode(AuthenticationCode $authenticationCode): self
    {
        if (!$this->authenticationCodes->contains($authenticationCode)) {
            $this->authenticationCodes[] = $authenticationCode;
            $authenticationCode->setUser($this);
        }

        return $this;
    }

    public function removeAuthenticationCode(AuthenticationCode $authenticationCode): self
    {
        if ($this->authenticationCodes->removeElement($authenticationCode)) {
            // set the owning side to null (unless already changed)
            if ($authenticationCode->getUser() === $this) {
                $authenticationCode->setUser(null);
            }
        }

        return $this;
    }

    public function getLoginAttempt(): ?int
    {
        return $this->loginAttempt;
    }

    public function setLoginAttempt(int $loginAttempt): self
    {
        $this->loginAttempt = $loginAttempt;

        return $this;
    }

    public function getBlocked(): ?int
    {
        return $this->blocked;
    }

    public function setBlocked(int $blocked): self
    {
        $this->blocked = $blocked;

        return $this;
    }

    public function getBlockedAt(): ?\DateTimeInterface
    {
        return $this->blockedAt;
    }

    public function setBlockedAt(?\DateTimeInterface $blockedAt): self
    {
        $this->blockedAt = $blockedAt;

        return $this;
    }
}
