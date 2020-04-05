<?php

namespace App\Controller;

use App\Entity\Club;
use App\Entity\Reservation;
use App\Entity\Session;
use App\Entity\User;
use App\Helper\ReservationHelper;
use App\Helper\UserHelper;
use App\Manager\ReservationManager;
use App\Repository\ReservationRepository;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends AbstractController
{
	/** @var EntityManagerInterface */
	private $entityManager;
	/** @var ReservationRepository|ObjectRepository */
	private $manager;
	/** @var ReservationManager */
	private $reservationManager;
	/** @var ReservationHelper */
	private $reservationHelper;
	/** @var UserHelper */
	private $userHelper;
	/** @var Utils */
	private $utils;

	/**
	 * @param EntityManagerInterface $entityManager
	 * @param ReservationManager $reservationManager
	 * @param ReservationHelper $reservationHelper
	 * @param UserHelper $userHelper
	 * @param Utils $utils
	 */
	public function __construct(EntityManagerInterface $entityManager, ReservationManager $reservationManager, ReservationHelper $reservationHelper, UserHelper $userHelper, Utils $utils)
	{
		$this->entityManager = $entityManager;
		$this->manager = $entityManager->getRepository(Reservation::class);
		$this->reservationManager = $reservationManager;
		$this->reservationHelper = $reservationHelper;
		$this->userHelper = $userHelper;
		$this->utils = $utils;
	}

	/**
	 * @param int $id
	 * @param int $sessionId
	 *
	 * @return JsonResponse
	 */
	public function createReservation(int $id, int $sessionId)
	{
		/** @var Session $session */
		$session = $this->entityManager->getRepository(Session::class)->getClubSessionById($id, $sessionId);
		if (null === $session) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'session_not_found');
		}

		if ($session->getRemainingPlaces() == 0) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'session_full');
		}

		/** @var User $user */
		$user = $this->getUser();

		if ($this->reservationHelper->hasAlreadyReservated($session, $user)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'reservation_already_done');
		}

		$this->reservationManager->createReservation($session, $user);

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'reservation_done');

	}

	/**
	 * @param Request $request
	 * @param int $id
	 * @param int $sessionId
	 * @param int|null $reservationId
	 *
	 * @return JsonResponse
	 *
	 * @throws NonUniqueResultException
	 */
	public function removeReservation(Request $request, int $id, int $sessionId, ?int $reservationId)
	{
		$club = $this->entityManager->getRepository(Club::class)->find($id);
		if (!$club) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
		}

		$reservation = null;
		/** @var User $user */
		$user = $this->getUser();


		if ($request->query->has('mobileApp')) {
			/** @var Reservation $reservation */
			$reservation = $this->manager->getUserReservationByClubAndSessionId($id, $sessionId, $user->getId());
		} else {

			if (!$this->userHelper->isClubOwner($user, $club)) {
				return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'permission_not_authorized');
			}

			/** @var Reservation $reservation */
			$reservation = $this->manager->getReservationByClubAndSessionId($id, $sessionId, $reservationId);
		}

		if (!$reservation) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'reservation_not_found');
		}

		$this->reservationManager->deleteReservation($reservation);

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'reservation_removed');

	}

	/**
	 * @param int $id
	 * @param int $sessionId
	 *
	 * @return JsonResponse
	 */
	public function getSessionReservations(int $id, int $sessionId)
	{
		$reservations = $this->manager->getReservationsSessionById($id, $sessionId);
		if (empty($reservations)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'reservations_not_found');
		}

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $this->reservationHelper->getSessionReservations($reservations));
	}
}