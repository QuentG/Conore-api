<?php

namespace App\Helper;

use App\Entity\Club;
use App\Entity\Session;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\Request;

class SessionHelper
{
	/** @var SessionRepository|ObjectRepository */
	private $manager;

	/**
	 * @param EntityManagerInterface $entityManager
	 */
	public function __construct(EntityManagerInterface $entityManager)
	{
		$this->manager = $entityManager->getRepository(Session::class);
	}

	/**
	 * @param int $id
	 * @param Request $request
	 * @param Club $club
	 *
	 * @return array
	 */
	public function getClubSessions(int $id, Request $request, Club $club)
	{
		$clubSession = [];

		if ($request->query->has('day')) {
			$datetime = $request->query->get('day');
			$sessions = $this->manager->getSessionsByDatetime($id, $datetime);

			/** @var Session $session */
			foreach ($sessions as $session) {
				$clubSession[] = $this->getSessionInfos($session, $request->query->has('userId') ? $request->query->get('userId') : null);
			}

		} else {
			foreach ($club->getSessions() as $session) {
				$clubSession[] = $this->getSessionInfos($session, $request->query->has('userId') ? $request->query->get('userId') : null);
			}
		}

		return $clubSession;
	}

	/**
	 * @param Session $session

	 * @param null $userId
	 * @return array
	 */
	public function getSessionInfos(Session $session, $userId = null)
	{
		$sessionInfos = [
			'id' => $session->getId(),
			'duration' => $session->getDuration(),
			'places' => $session->getPlaces(),
			'remaining_places' => $session->getPlaces() - $session->getReservations()->count(),
			'event_at' => $session->getSessionAt()->getTimestamp(),
			'created_at' => $session->getCreatedAt()->getTimestamp()
		];

		if (!is_null($userId)) {
			$sessionInfos['has_reservated'] = $this->userHasReservated($session, $userId);
		}

		return $sessionInfos;
	}

	/**
	 * @param Session $session
	 * @param $userId
	 *
	 * @return bool
	 */
	private function userHasReservated(Session $session, $userId)
	{
		$reservations = $session->getReservations();

		foreach ($reservations as $reservation) {
			foreach ($reservation->getUsers() as $user) {
				if ($user->getId() == $userId) {
					return true;
				}
				continue;
			}
		}

		return false;
	}
}