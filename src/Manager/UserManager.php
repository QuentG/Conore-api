<?php

namespace App\Manager;

use App\Entity\Club;
use App\Entity\Session;
use App\Entity\User;
use App\Helper\StripeHelper;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\EntityFieldEnum;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserManager
{
	/** @var EntityManagerInterface */
	private $entityManager;
	/** @var UserPasswordEncoderInterface */
	private $passwordEncoder;
	/** @var StripeHelper */
	private $stripeHelper;

	/**
	 * @param EntityManagerInterface $entityManager
	 * @param UserPasswordEncoderInterface $passwordEncoder
	 */
	public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder, StripeHelper $stripeHelper)
	{
		$this->entityManager = $entityManager;
		$this->passwordEncoder = $passwordEncoder;
		$this->stripeHelper = $stripeHelper;
	}

	/**
	 * @param string $email
	 * @param string $password
	 *
	 * @return User
	 */
	public function createAdmin(string $email, string $password)
	{
		$user = new User();
		$user->setEmail($email)
			->setPassword($this->passwordEncoder->encodePassword($user, $password))
			->setFirstConnection(false);

		$this->entityManager->persist($user);
		$this->entityManager->flush();

		return $user;
	}

	/**
	 * @param string $email
	 * @param string $password
	 * @param string $firstname
	 * @param string $lastname
	 * @param Club $club
	 *
	 * @return User
	 */
	public function createCustomer(string $email, string $password, string $firstname, string $lastname, Club $club)
	{
		$user = new User();
		$user->setEmail($email)
			->setFirstName($firstname)
			->setLastName($lastname)
			->setPassword($this->passwordEncoder->encodePassword($user, $password))
			->addClub($club);

		$this->entityManager->persist($user);
		$this->entityManager->flush();

		return $user;
	}

	/**
	 * @param User $user
	 * @param $jsonData
	 * @param Club|null $club
	 */
	public function editUser(User $user, $jsonData, ?Club $club = null)
	{
		if (array_key_exists(EntityFieldEnum::EMAIL_FIELD, $jsonData)) {
			$user->setEmail($jsonData[EntityFieldEnum::EMAIL_FIELD]);
		}

		if (array_key_exists(EntityFieldEnum::PASSWORD_FIELD, $jsonData)) {
			$user->setPassword($this->passwordEncoder->encodePassword($user, $jsonData[EntityFieldEnum::PASSWORD_FIELD]));
		}

		if (array_key_exists(EntityFieldEnum::USERNAME_FIELD, $jsonData)) {
			$user->setUsername($jsonData[EntityFieldEnum::USERNAME_FIELD]);
		}

		if (array_key_exists(EntityFieldEnum::FIRSTNAME_FIELD, $jsonData)) {
			$user->setFirstName($jsonData[EntityFieldEnum::FIRSTNAME_FIELD]);
		}

		if (array_key_exists(EntityFieldEnum::LASTNAME_FIELD, $jsonData)) {
			$user->setLastName($jsonData[EntityFieldEnum::LASTNAME_FIELD]);
		}

		if (array_key_exists(EntityFieldEnum::FIRST_CONNECTION_FIELD, $jsonData)) {
			$user->setFirstConnection(false);
		}

		if ($club instanceof Club) {
			$user->addClub($club);
		}

		$this->entityManager->flush();
	}

	/**
	 * @param Session $session
	 *
	 * @return array
	 */
	public function getUsersPushNotificationToken(Session $session)
	{
		$users = [];
		foreach ($session->getClub()->getUsers() as $user) {
			if (null === $user->getPushNotificationToken()) {
				continue;
			}

			$users[] = $user->getPushNotificationToken();
		}

		return $users;
	}

	/**
	 * @param User $user
	 * @param string $stripeCustomerId
	 */
	public function addStripeInfos(User $user, string $stripeCustomerId)
	{
		$user->setStripeCustomerId($stripeCustomerId);
		$this->entityManager->flush();
	}

	/**
	 * @param User $user
	 * @param Club $club
	 */
	public function setInactiveSubscriptions(User $user, Club $club)
	{
		if ($user->hasSubscriptions()) {
			foreach ($user->getSubscriptions() as $subscription) {
				if ($this->stripeHelper->isClubSubscription($subscription, $club)) {
					$subscription->setActive(false);
				}
			}
			$this->entityManager->flush();
		}
	}

}