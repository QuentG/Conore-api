<?php

namespace App\Helper;

use App\Entity\Club;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ClubHelper
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager->getRepository(Club::class);
    }

    /**
     * @param Club $club
     * @param bool $onlyUsers
     *
     * @return array
     */
    public function getClubInfos(Club $club, $onlyUsers = false)
    {
        $tabUsers = [];

        foreach ($club->getUsers() as $user) {
            $tabUsers[] = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'email' => $user->getEmail()
            ];
        }

        if ($onlyUsers) {
            return $tabUsers;
        }

        return [
            'id' => $club->getId(),
            'name' => $club->getName(),
            'created_at' => $club->getCreatedAt()->getTimestamp(),
            'address' => $club->getAddress(),
            'city' => $club->getCity(),
            'zip_code' => $club->getZipCode(),
            'phone' => $club->getPhone(),
            'stripe_connected' => $club->getStripeAccountId() === null ? false : true,
            'owner' => [
                'firstname' => $club->getOwner()->getFirstName(),
                'lastname' => $club->getOwner()->getLastName(),
                'email' => $club->getOwner()->getEmail()
            ],
            'users' => $tabUsers
        ];
    }

    /**
     * @param string $name
     * @param User $user
     *
     * @return bool
     */
    public function clubAlreadyExist(string $name, User $user)
    {
        $club = $this->entityManager->findOneBy([
            'name' => $name,
            'owner' => $user
        ]);

        return !is_null($club);
    }

    /**
     * @param Club $club
     * @param string $email
     *
     * @return bool
     */
    public function userIsAlreadyInClub(Club $club, string $email)
    {
        foreach ($club->getUsers() as $user) {
            if ($user->getEmail() === $email) {
                return true;
            }
        }

        return false;
    }

}