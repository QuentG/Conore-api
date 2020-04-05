<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface
{
	use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=70)
     */
    private $email;

	/**
	 * @ORM\Column(type="string", length=30, nullable=true)
	 */
	private $username;

	/**
	 * @ORM\Column(type="string", length=30, nullable=true)
	 */
	private $firstName;

	/**
	 * @ORM\Column(type="string", length=30, nullable=true)
	 */
	private $lastName;

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
	 * @var ApiToken[]|Collection
	 *
	 * @ORM\OneToMany(targetEntity="App\Entity\ApiToken", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $apiToken;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Club", mappedBy="owner")
     */
    private $ownerClubs;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Club", inversedBy="users")
     */
    private $clubs;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Reservation", mappedBy="users")
     */
    private $reservations;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pushNotificationToken;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripeCustomerId;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Subscription", mappedBy="user")
     */
    private $subscriptions;

    /**
     * @ORM\Column(type="boolean")
     */
    private $firstConnection = true;

    public function __construct()
    {
        $this->ownerClubs = new ArrayCollection();
        $this->clubs = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
    }

	/**
	 * @return string
	 */
	public function getFullName()
	{
		return "{$this->firstName} ' ' {$this->lastName}";
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

	public function getFirstName(): ?string
	{
		return $this->firstName;
	}

	public function setFirstName(?string $firstName): self
	{
		$this->firstName = $firstName;

		return $this;
	}

	public function getLastName(): ?string
	{
		return $this->lastName;
	}

	public function setLastName(?string $lastName): self
	{
		$this->lastName = $lastName;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getTheUsername(): ?string
	{
		return $this->username;
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
	* @param string|null $username
	* @return $this
	*/
	public function setUsername(?string $username): self
	{
		$this->username = $username;

		return $this;
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

	/**
	 * @return ApiToken[]|Collection
	 */
	public function getApiToken()
         	{
         		return $this->apiToken;
         	}

    /**
     * @return Collection|Club[]
     */
    public function getOwnerClubs(): Collection
    {
        return $this->ownerClubs;
    }

	/**
	 * @return bool
	 */
    public function isClubOwner()
	{
		return !$this->ownerClubs->isEmpty();
	}

    public function addOwnerClub(Club $ownerClub): self
    {
        if (!$this->ownerClubs->contains($ownerClub)) {
            $this->ownerClubs[] = $ownerClub;
            $ownerClub->setOwner($this);
        }

        return $this;
    }

    public function removeOwnerClub(Club $ownerClub): self
    {
        if ($this->ownerClubs->contains($ownerClub)) {
            $this->ownerClubs->removeElement($ownerClub);
            // set the owning side to null (unless already changed)
            if ($ownerClub->getOwner() === $this) {
                $ownerClub->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Club[]
     */
    public function getClubs(): Collection
    {
        return $this->clubs;
    }

    public function addClub(Club $club): self
    {
        if (!$this->clubs->contains($club)) {
            $this->clubs[] = $club;
        }

        return $this;
    }

    public function removeClub(Club $club): self
    {
        if ($this->clubs->contains($club)) {
            $this->clubs->removeElement($club);
        }

        return $this;
    }

    /**
     * @return Collection|Reservation[]
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

	/**
	 * @return bool
	 */
    public function hasReservations(): bool
	{
		return !$this->reservations->isEmpty();
	}

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->addUser($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->contains($reservation)) {
            $this->reservations->removeElement($reservation);
            $reservation->removeUser($this);
        }

        return $this;
    }

    public function getPushNotificationToken(): ?string
    {
        return $this->pushNotificationToken;
    }

    public function setPushNotificationToken(?string $pushNotificationToken): self
    {
        $this->pushNotificationToken = $pushNotificationToken;

        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): self
    {
        $this->stripeCustomerId = $stripeCustomerId;

        return $this;
    }

    /**
     * @return Collection|Subscription[]
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

	/**
	 * @return bool
	 */
    public function hasSubscriptions(): bool
	{
		return !$this->subscriptions->isEmpty();
	}

    public function addSubscription(Subscription $subscription): self
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions[] = $subscription;
            $subscription->addUser($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): self
    {
        if ($this->subscriptions->contains($subscription)) {
            $this->subscriptions->removeElement($subscription);
            $subscription->removeUser($this);
        }

        return $this;
    }

    public function getFirstConnection(): ?bool
    {
        return $this->firstConnection;
    }

    public function setFirstConnection(bool $firstConnection): self
    {
        $this->firstConnection = $firstConnection;

        return $this;
    }

}
