<?php

namespace App\Manager;

use App\Entity\Club;
use App\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\EntityFieldEnum;

class SessionManager
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
	 * @param Club $club
	 * @param $jsonData
	 *
	 * @return Session
	 */
	public function createSession(Club $club, $jsonData)
	{
		$session = new Session();
		$session->setDuration($jsonData[EntityFieldEnum::DURATION_FIELD])
			->setPlaces($jsonData[EntityFieldEnum::PLACES_FIELD])
			->setSessionAt((new \DateTime())->setTimestamp($jsonData[EntityFieldEnum::EVENT_AT_FIELD]))
			->setClub($club);

		$this->entityManager->persist($session);
		$this->entityManager->flush();

		return $session;
	}

	/**
	 * @param Session $session
	 * @param $jsonData
	 *
	 * @return int
	 */
	public function editSession(Session $session, $jsonData)
	{
		$updated = 0;

		if (array_key_exists(EntityFieldEnum::PLACES_FIELD, $jsonData)) {
			$session->setPlaces($jsonData[EntityFieldEnum::PLACES_FIELD]);
			$updated++;
		}

		if (array_key_exists(EntityFieldEnum::DURATION_FIELD, $jsonData)) {
			$session->setDuration($jsonData[EntityFieldEnum::DURATION_FIELD]);
			$updated++;
		}

		if (array_key_exists(EntityFieldEnum::EVENT_AT_FIELD, $jsonData)) {
			$session->setSessionAt((new \DateTime())->setTimestamp($jsonData[EntityFieldEnum::EVENT_AT_FIELD]));
			$updated++;
		}

		$this->entityManager->flush();

		return $updated;
	}
}