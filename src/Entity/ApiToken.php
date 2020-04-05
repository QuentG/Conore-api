<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ApiTokenRepository")
 */
class ApiToken
{
	use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=40, unique=true, nullable=false)
     */
    private $accessToken;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $expirationDate;

    /**
     * @ORM\Column(type="string", length=40, unique=true, nullable=false)
     */
    private $refreshToken;

	/**
	 * @var User
	 *
	 * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="apiToken")
	 * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
	 */
	protected $user;

	/**
	 * @param string $accessToken
	 * @param \DateTime $expirationDate
	 * @param string $refreshToken
	 * @param User $user
	 */
    public function __construct(string $accessToken, \DateTime $expirationDate, string $refreshToken, User $user)
	{
		$this->accessToken = $accessToken;
		$this->expirationDate = $expirationDate;
		$this->refreshToken = $refreshToken;
		$this->user = $user;
	}

	/**
	 * @return int|
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

	/**
	 * @return \DateTimeInterface
	 */
    public function getExpirationDate(): \DateTimeInterface
    {
        return $this->expirationDate;
    }

	/**
	 * @return string
	 */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

	public function getUser(): User
	{
		return $this->user;
	}

	/**
	 * @param \DateTime $now
	 *
	 * @return bool
	 */
    public function isValid(\DateTime $now): bool
	{
		return $now < $this->expirationDate;
	}

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function setAccessToken(?string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function setExpirationDate(?\DateTimeInterface $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }
}
