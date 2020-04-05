<?php

namespace App\Manager;

use App\Entity\Reservation;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ReservationManager
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
	 * @param Session $session
	 * @param User $user
	 */
	public function createReservation(Session $session, User $user)
	{
		$reservation = new Reservation();
		$reservation->setSession($session)
			->addUser($user);

		$this->entityManager->persist($reservation);
		$this->entityManager->flush();
	}

	/**
	 * @param Reservation $reservation
	 */
	public function deleteReservation(Reservation $reservation)
	{
		$this->entityManager->remove($reservation);
		$this->entityManager->flush();
	}
}