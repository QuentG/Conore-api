<?php

namespace App\Manager;

use App\Entity\Club;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\EntityFieldEnum;

class ClubManager
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $name
     * @param User $user
     */
    public function createClub(string $name, User $user)
    {
        $club = new Club();
        $club->setName($name);
        $club->setOwner($user);

        $this->entityManager->persist($club);
        $this->entityManager->flush();
    }

    /**
     * @param Club $club
     * @param $jsonData
     */
    public function editClub(Club $club, $jsonData)
    {
        if (array_key_exists(EntityFieldEnum::NAME_FIELD, $jsonData)) {
            $club->setName($jsonData[EntityFieldEnum::NAME_FIELD]);
        }

        if (array_key_exists(EntityFieldEnum::ADDRESS_FIELD, $jsonData)) {
            $club->setAddress($jsonData[EntityFieldEnum::ADDRESS_FIELD]);
        }

        if (array_key_exists(EntityFieldEnum::CITY_FIELD, $jsonData)) {
            $club->setCity($jsonData[EntityFieldEnum::CITY_FIELD]);
        }

        if (array_key_exists(EntityFieldEnum::ZIPCODE_FIELD, $jsonData)) {
            $club->setZipCode($jsonData[EntityFieldEnum::ZIPCODE_FIELD]);
        }

        if (array_key_exists(EntityFieldEnum::PHONE_FIELD, $jsonData)) {
            $club->setPhone($jsonData[EntityFieldEnum::PHONE_FIELD]);
        }

        $this->entityManager->flush();
    }

    /**
     * @param Club $club
     */
    public function deleteClub(Club $club)
    {
        $this->entityManager->remove($club);
        $this->entityManager->flush();
    }

    /**
     * @param Club $club
     * @param User $user
     */
    public function addMember(Club $club, User $user)
    {
        $club->addUser($user);
        $this->entityManager->flush();
    }

	/**
	 * @param Club $club
	 * @param $stripeAccountId
	 * @param $stripeAccessToken
	 */
    public function addStripeInfos(Club $club, string $stripeAccountId, string $stripeAccessToken)
	{
		$club->setStripeAccountId($stripeAccountId);
		$club->setStripeAccessToken($stripeAccessToken);
		$this->entityManager->flush();
	}
}