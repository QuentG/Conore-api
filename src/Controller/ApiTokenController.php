<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Enum\EntityFieldEnum;
use App\Manager\ApiTokenManager;
use App\Repository\ApiTokenRepository;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenController extends AbstractController
{
	/** @var ApiTokenRepository|ObjectRepository */
	private $manager;
	/** @var ApiTokenManager */
	private $apiTokenManager;
	/** @var Utils */
	private $utils;

	/**
	 * @param Utils $utils
	 * @param EntityManagerInterface $manager
	 * @param ApiTokenManager $apiTokenManager
	 */
	public function __construct(EntityManagerInterface $manager, ApiTokenManager $apiTokenManager, Utils $utils)
	{
		$this->manager = $manager->getRepository(ApiToken::class);
		$this->apiTokenManager = $apiTokenManager;
		$this->utils = $utils;
	}

	/**
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function refresh(Request $request)
	{
		// Decode request content
		$jsonData = \json_decode($request->getContent(), true);
		if (null === $jsonData) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
		}

		if (!array_key_exists(EntityFieldEnum::REFRESH_TOKEN_FIELD, $jsonData)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'missing_fields');
		}

		$apiToken = $this->manager->findOneBy([
			'refreshToken' => $jsonData[EntityFieldEnum::REFRESH_TOKEN_FIELD]
		]);

		if (!$apiToken) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'refresh_token_not_found');
		}

		$newApiToken = $this->apiTokenManager->refreshToken($apiToken);

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'access_token_refeshed', [
            'user' => $newApiToken->getUser()->getEmail(),
            'accessToken' => $newApiToken->getAccessToken(),
            'refreshToken' => $newApiToken->getRefreshToken(),
		]);
	}

	/**
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function revoke(Request $request)
	{
		$jsonData = \json_decode($request->getContent(), true);
		if (null === $jsonData) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
		}

		if (!array_key_exists(EntityFieldEnum::ACCESS_TOKEN_FIELD, $jsonData)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'missing_fields');
		}

		$apiToken = $this->manager->findOneBy([
			'accessToken' => $jsonData[EntityFieldEnum::ACCESS_TOKEN_FIELD],
			'user' => $this->getUser()
		]);

		if (!$apiToken) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'access_token_not_found');
		}

		$this->apiTokenManager->revokeToken($apiToken);

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'access_token_revoked');
	}
}