<?php

namespace App\Manager\Alerting;

use App\ApiConnector\FirebaseConnector;
use App\Entity\Session;
use App\Manager\UserManager;

class AlertingManager
{
	private const TIME_TO_LIVE = 86400; // Firebase try to send the notification to device during 86400 seconds (24h).
	private const MAX_BYTES = 4096;

	/** @var FirebaseConnector $firebaseClient */
	private $firebaseClient;
	/** @var UserManager */
	private $userManager;

	/**
	 * @param FirebaseConnector $firebaseConnector
	 * @param UserManager $userManager
	 */
	public function __construct(FirebaseConnector $firebaseConnector, UserManager $userManager)
	{
		$this->firebaseClient = $firebaseConnector;
		$this->userManager = $userManager;
	}

	/**
	 * @param string $notificationTitle
	 * @param string $notificationBody
	 * @param Session $session
	 */
	public function send(string $notificationTitle, string $notificationBody, Session $session)
	{
		$recipients = $this->userManager->getUsersPushNotificationToken($session); // Get user's token
		if (empty($recipients)) { // registration_ids cannot be empty
			return;
		}

		$data = [
			'registration_ids' => $recipients,
			'time_to_live' => self::TIME_TO_LIVE,
			'data' => [
				'club_id' => $session->getClub()->getId(),
				'club_name' => $session->getClub()->getName(),
				'session_id' => $session->getId(),
				'event_at' => $session->getSessionAt()->getTimestamp(),
				'duration' => $session->getDuration(),
				'places' => $session->getRemainingPlaces()
			],
			'notification' => [
				'title' => mb_strcut($notificationTitle, 0, self::MAX_BYTES),
				'body' => mb_strcut($notificationBody, 0, self::MAX_BYTES),
			],
		];

		$this->firebaseClient->send($data); // Try to send notifications
	}

}
