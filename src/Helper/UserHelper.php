<?php

namespace App\Helper;

use App\Entity\Club;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class UserHelper
{
    /** @var UserRepository|ObjectRepository */
    private $manager;
	/** @var StripeHelper */
	private $stripeHelper;

    /**
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager, StripeHelper $stripeHelper)
    {
        $this->manager = $manager->getRepository(User::class);
        $this->stripeHelper = $stripeHelper;
    }

    /**
     * @return array
     */
    public function getAllUsers()
    {
        $users = $this->manager->findAll();
        $tabUsers = [];

        foreach ($users as $user) {
            $tabUsers[] = $this->getUserInfos($user);
        }

        return $tabUsers;
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function getUserInfos(User $user)
    {
        $clubs = [];
		foreach ($user->getClubs() as $key => $club) {
			$clubs[$key]['clubId'] = $club->getId();
			$clubs[$key]['reservations'] = [];
			$clubs[$key]['subscriptions'] = $user->hasSubscriptions() ? [] : (object) [];
			$clubs[$key]['subTerminated'] = $user->hasSubscriptions() ? [] : (object) [];

			if ($user->hasReservations()) { // Check user reservations
				foreach ($user->getReservations() as $reservation) {
					if ($club->getId() !== $reservation->getSession()->getClub()->getId()) { // For this club
						continue;
					}

					$clubs[$key]['reservations'][] = $reservation->getSession()->getId();
				}
			}
			if ($user->hasSubscriptions()) { // Check user subscriptions for this club
				foreach ($user->getSubscriptions() as $subscription) {
					if ($this->stripeHelper->isClubSubscription($subscription, $club)) {
						$clubs[$key][$subscription->isActive() ? 'subscriptions' : 'subTerminated'][] = [
							'name' => $subscription->getName(),
							'amount' => $subscription->getPrice(),
							'start_date' => $subscription->getStartDate()->getTimestamp(),
							'end_date' => $subscription->getEndDate()->getTimestamp(),
							'frequency' => $subscription->getFrequency()
						];
					}
				}
			}
		}

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getTheUsername(),
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'clubs' => $clubs
        ];
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function getUserOwnerClubs(User $user)
    {
        $clubs = [];
        foreach ($user->getOwnerClubs() as $club) {
            $clubs[] = [
                'id' => $club->getId(),
                'name' => $club->getName(),
                'created_at' => $club->getCreatedAt()->getTimestamp(),
            ];
        }

        return $clubs;
    }

    /**
     * @param $email
     * @return User
     */
    public function retrieveUser($email)
    {
        return $this->manager->findOneBy([
            'email' => $email
        ]);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function userAlreadyExist(string $email)
    {
        $user = $this->manager->findOneBy([
            'email' => $email
        ]);

        return !is_null($user);
    }

	/**
	 * @param User $user
	 * @param Club $club
	 *
	 * @return bool
	 */
    public function isClubOwner(User $user, Club $club)
	{
		return $user->getEmail() == $club->getOwner()->getEmail();
	}

}