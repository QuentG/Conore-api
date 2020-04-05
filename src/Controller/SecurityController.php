<?php

namespace App\Controller;

use App\Entity\Club;
use App\Entity\User;
use App\Helper\ClubHelper;
use App\Helper\UserHelper;
use App\Manager\ApiTokenManager;
use App\Manager\UserManager;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\EntityFieldEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends AbstractController
{
	/** @var EntityManagerInterface */
	private $entityManager;
	/** @var ApiTokenManager */
	private $apiTokenManager;
	/** @var Utils */
	private $utils;
	/** @var UserManager */
	private $userManager;
	/** @var UserHelper */
	private $userHelper;
	/** @var ClubHelper */
	private $clubHelper;

	/**
	 * @param EntityManagerInterface $entityManager
	 * @param ApiTokenManager $apiTokenManager
	 * @param Utils $utils
	 * @param UserManager $userManager
	 * @param UserHelper $userHelper
	 * @param ClubHelper $clubHelper
	 */
	public function __construct
	(
		EntityManagerInterface $entityManager,
		ApiTokenManager $apiTokenManager,
		Utils $utils,
		UserManager $userManager,
		UserHelper $userHelper,
		ClubHelper $clubHelper
	)
	{
		$this->entityManager = $entityManager;
		$this->apiTokenManager = $apiTokenManager;
		$this->utils = $utils;
		$this->userManager = $userManager;
		$this->userHelper = $userHelper;
		$this->clubHelper = $clubHelper;
	}

	/**
	 * Check in Security/ApiAuthenticator.php to check the login process
	 */
	public function login()
	{
		$accessToken = null;
		$refreshToken = null;

		/** @var User $user */
		$user = $this->getUser();

		foreach ($user->getApiToken() as $item) {
			$accessToken = $item->getAccessToken();
			$refreshToken = $item->getRefreshToken();
		}

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'logged_successfully', [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
			'first_connection' => $user->getFirstConnection()
		]);
	}

	public function logout() {}

	/**
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function registerCustomer(Request $request)
	{
		$jsonData = \json_decode($request->getContent(), true);
		if (null === $jsonData) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
		}

		if (!array_key_exists(EntityFieldEnum::EMAIL_FIELD, $jsonData) || !array_key_exists(EntityFieldEnum::FIRSTNAME_FIELD, $jsonData) || !array_key_exists(EntityFieldEnum::LASTNAME_FIELD, $jsonData) || !array_key_exists(EntityFieldEnum::CLUB_ID_FIELD, $jsonData)){
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'missing_fields');
		}

		if (!$this->utils->validate($jsonData[EntityFieldEnum::EMAIL_FIELD])) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_email_format');
		}

		$club = $this->entityManager->getRepository(Club::class)->find(
			(int) $jsonData[EntityFieldEnum::CLUB_ID_FIELD]
		);

		if (!$club) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
		}

		if ($this->userHelper->userAlreadyExist($jsonData[EntityFieldEnum::EMAIL_FIELD])) {
			if ($this->clubHelper->userIsAlreadyInClub($club, $jsonData[EntityFieldEnum::EMAIL_FIELD])) {
				return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'user_already_in_club');
			} else {
				$this->userManager->editUser(
					$this->userHelper->retrieveUser($jsonData[EntityFieldEnum::EMAIL_FIELD]),
					$jsonData
				);
				return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'user_added_in_club');
			}
		}

		$password = sha1(random_bytes(8)); // Generate random password

		$user = $this->userManager->createCustomer(
			$jsonData[EntityFieldEnum::EMAIL_FIELD],
			$password,
			$jsonData[EntityFieldEnum::FIRSTNAME_FIELD],
			$jsonData[EntityFieldEnum::LASTNAME_FIELD],
			$club
		);

		$this->apiTokenManager->create($user);

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'user_added_in_club', [
			'email' => $user->getEmail(),
			'password' => $password
		]);
	}

	/**
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function registerAdmin(Request $request)
	{
		$jsonData = \json_decode($request->getContent(), true);
		if (null === $jsonData) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
		}

		if (!array_key_exists(EntityFieldEnum::EMAIL_FIELD, $jsonData) || !array_key_exists(EntityFieldEnum::PASSWORD_FIELD, $jsonData)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'missing_fields');
		}

		$email = $jsonData[EntityFieldEnum::EMAIL_FIELD];
		$password = $jsonData[EntityFieldEnum::PASSWORD_FIELD];

        if (!$this->utils->validate($email)) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_email_format');
        }

		if ($this->userHelper->userAlreadyExist($email)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'email_already_used');
		}

		$user = $this->userManager->createAdmin($email, $password);

		$tokens = $this->apiTokenManager->create($user);

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'account_created', [
            'accessToken' => $tokens->getAccessToken(),
            'refreshToken' => $tokens->getRefreshToken(),
		]);
	}
}