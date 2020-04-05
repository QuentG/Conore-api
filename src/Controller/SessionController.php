<?php

namespace App\Controller;

use App\Entity\Club;
use App\Entity\Session;
use App\Helper\SessionHelper;
use App\Manager\Alerting\AlertingManager;
use App\Manager\SessionManager;
use App\Repository\SessionRepository;
use App\Utils\Utils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\EntityFieldEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionController extends AbstractController
{
	/** @var SessionRepository|ObjectRepository */
	private $manager;
	/** @var EntityManagerInterface */
	private $entityManager;
	/** @var SessionManager */
	private $sessionManager;
	/** @var SessionHelper */
	private $sessionHelper;
	/** @var AlertingManager */
	private $alertingManager;
	/** @var Utils */
	private $utils;

	/**
	 * @param EntityManagerInterface $entityManager
	 * @param SessionManager $sessionManager
	 * @param SessionHelper $sessionHelper
	 * @param AlertingManager $alertingManager
	 * @param Utils $utils
	 */
	public function __construct
	(
		EntityManagerInterface $entityManager,
		SessionManager $sessionManager,
		SessionHelper $sessionHelper,
		AlertingManager $alertingManager,
		Utils $utils
	)
	{
		$this->manager = $entityManager->getRepository(Session::class);
		$this->entityManager = $entityManager;
		$this->sessionManager = $sessionManager;
		$this->sessionHelper = $sessionHelper;
		$this->alertingManager = $alertingManager;
		$this->utils = $utils;
	}

	/**
	 * @param Request $request
	 * @param int $id
	 *
	 * @return JsonResponse
	 * @throws \Exception
	 */
	public function createSession(Request $request, int $id)
	{
		$jsonData = \json_decode($request->getContent(), true);
		if (null === $jsonData) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
		}

		if (!array_key_exists(EntityFieldEnum::EVENT_AT_FIELD, $jsonData) || !array_key_exists(EntityFieldEnum::DURATION_FIELD, $jsonData) || !array_key_exists(EntityFieldEnum::PLACES_FIELD, $jsonData)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'missing_fields');
		}

		if (!$this->utils->checkDate($jsonData[EntityFieldEnum::EVENT_AT_FIELD])) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'date_cannot_be_lower_than_now');
		}

		$club = $this->entityManager->getRepository(Club::class)->find($id);
		if (!$club) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
		}

		$session = $this->sessionManager->createSession($club, $jsonData);

		// Send notifications
		$this->alertingManager->send(
			'Conore - ' . $session->getClub()->getName(),
			'Une nouvelle session vient d\'être créée !',
			$session
		);

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'session_created');
	}

	/**
	 * @param int $id
	 *
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function getClubSessions(Request $request, int $id)
	{
		$club = $this->entityManager->getRepository(Club::class)->find($id);
		if (!$club) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
		}

		$clubSession = $this->sessionHelper->getClubSessions($id, $request, $club);

		// Sort by event_at ASC
		usort($clubSession, function ($a, $b) {
			return $a['event_at'] <=> $b['event_at'];
		});

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $clubSession);
	}

	/**
	 * @param int $id
	 * @param int $sessionId
	 *
	 * @return JsonResponse
	 */
	public function getClubSession(int $id, int $sessionId)
	{
		// Retrieve the session
		$session = $this->manager->getClubSessionById($id, $sessionId);
		if (!$session) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'session_not_found');
		}

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $this->sessionHelper->getSessionInfos($session));
	}

	/**
	 * @param Request $request
	 * @param int $id
	 * @param int $sessionId
	 *
	 * @return JsonResponse
	 */
	public function editSession(Request $request, int $id, int $sessionId)
	{
		$jsonData = \json_decode($request->getContent(), true);
		if (null === $jsonData) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
		}

		/** @var Session $session */
		$session = $this->manager->getClubSessionById($id, $sessionId);
		if (!$session) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'session_not_found');
		}

		$updated = $this->sessionManager->editSession($session, $jsonData);

		if ($updated > 0) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'session_updated');
		}

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'nothing_updated');
	}

	/**
	 * @param int $id
	 * @param int $sessionId
	 *
	 * @return JsonResponse
	 */
	public function deleteClubSession(int $id, int $sessionId)
	{
		$session = $this->manager->getClubSessionById($id, $sessionId);
		if (!$session) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'session_not_found');
		}

		$this->entityManager->remove($session);
		$this->entityManager->flush();

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'session_deleted');
	}

}