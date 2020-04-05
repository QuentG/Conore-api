<?php

namespace App\Manager;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class ApiTokenManager
{
	private const TOKEN_VALIDATION_DURATION = 'P2D'; // 2 days

	/** @var EntityManagerInterface */
	private $entityManager;
	/** @var ApiTokenRepository|ObjectRepository */
	private $apiTokenRepository;

	/**
	 * @param EntityManagerInterface $entityManager
	 */
	public function __construct(EntityManagerInterface $entityManager)
	{
		$this->entityManager = $entityManager;
		$this->apiTokenRepository = $entityManager->getRepository(ApiToken::class);
	}

	/**
	 * @param User $user
	 *
	 * @return ApiToken
	 */
	public function create(User $user)
	{
		$tokens = $this->generateTokens();

		$apiToken = new ApiToken($tokens['accessToken'], $tokens['expirationDate'], $tokens['refreshToken'], $user);

		// Insert new ApiToken in DB
		$this->entityManager->persist($apiToken);
		$this->entityManager->flush();

		return $apiToken;
	}

	/**
	 * @param ApiToken $apiToken
	 *
	 * @return ApiToken
	 */
	public function refreshToken(ApiToken $apiToken)
	{
		$tokens = $this->generateTokens();

        $apiToken->setAccessToken($tokens['accessToken']);
        $apiToken->setRefreshToken($tokens['refreshToken']);
        $apiToken->setExpirationDate($tokens['expirationDate']);

		$this->entityManager->persist($apiToken);
		$this->entityManager->flush();

		return $apiToken;
	}

	/**
	 * @param ApiToken $apiToken
	 */
	public function revokeToken(ApiToken $apiToken)
	{
		$this->entityManager->remove($apiToken);
		$this->entityManager->flush();
	}

	/**
	 * @return array
	 */
	private function generateTokens()
	{
		$tokens = [];

		do {
			$accessToken = sha1(random_bytes(10));
		} while ($this->accessTokenAlreadyExists($accessToken));

		$tokens['accessToken'] = $accessToken;

		do {
			$refreshToken = sha1(random_bytes(10));
			$refreshToken = '__'.substr($refreshToken, 0, -2); // refreshToken starts by '__' to more easily identify it
		} while ($this->refreshTokenAlreadyExists($refreshToken));

		$tokens['refreshToken'] = $refreshToken;
		$tokens['expirationDate'] = (new \DateTime('now'))->add(new \DateInterval(self::TOKEN_VALIDATION_DURATION)); // ApiToken is valid 2 days

		return $tokens;
	}

	/**
	 * @param string $accessToken
	 *
	 * @return bool
	 */
	private function accessTokenAlreadyExists(string $accessToken)
	{
		$apiToken = $this->apiTokenRepository->findOneBy([
			'accessToken' => $accessToken
		]);

		return !is_null($apiToken);
	}

	/**
	 * @param string $refreshToken
	 *
	 * @return bool
	 */
	private function refreshTokenAlreadyExists(string $refreshToken)
	{
		$refreshToken = $this->apiTokenRepository->findOneBy([
			'refreshToken' => $refreshToken
		]);

		return !is_null($refreshToken);
	}

}