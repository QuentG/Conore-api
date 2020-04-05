<?php

namespace App\Security;

use App\Entity\ApiToken;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiTokenAuthenticator extends AbstractGuardAuthenticator
{
	private const HEADER_AUTH_TOKEN = 'X-CONORE-AUTH-TOKEN';

	/** @var EntityManagerInterface */
	private $entityManager;
	/** @var Utils */
	private $utils;

	/**
	 * @param EntityManagerInterface $entityManager
	 * @param Utils $utils
	 */
	public function __construct(EntityManagerInterface $entityManager, Utils $utils)
	{
		$this->entityManager = $entityManager;
		$this->utils = $utils;
	}

	/**
	 * - has required body for authentication.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function supports(Request $request)
	{
		return $request->headers->has(self::HEADER_AUTH_TOKEN);
	}

	/**
	 * @param Request $request
	 *
	 * @return array|mixed
	 */
	public function getCredentials(Request $request)
	{
		return [
			'accessToken' => $request->headers->get(self::HEADER_AUTH_TOKEN)
		];
	}

	/**
	 * @param mixed $credentials
	 * @param UserProviderInterface $userProvider
	 *
	 * @return UserInterface|null
	 */
	public function getUser($credentials, UserProviderInterface $userProvider)
	{
		if (!$credentials['accessToken']) {
			throw new AuthenticationException('token_was_empty');
		}

		$apiToken = $this->entityManager->getRepository(ApiToken::class)->findOneBy([
			'accessToken' => $credentials['accessToken'],
		]);

		if (is_null($apiToken)) {
			throw new AuthenticationException('token_not_found');
		}

		$now = new \DateTime('now');
		if (!$apiToken->isValid($now)) {
			throw new AuthenticationException('token_has_expired');
		}

		return $apiToken->getUser();
	}

	/**
	 * @param mixed $credentials
	 * @param UserInterface $user
	 *
	 * @return bool
	 */
	public function checkCredentials($credentials, UserInterface $user)
	{
		return true;
	}

	/**
	 * @param Request $request
	 * @param AuthenticationException $exception
	 *
	 * @return JsonResponse|Response|null
	 */
	public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
	{
		return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', $exception->getMessage());
	}

	/**
	 * @param Request $request
	 * @param TokenInterface $token
	 * @param string $providerKey
	 *
	 * @return Response|null
	 */
	public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
	{
		return null;
	}

	/**
	 * @param Request $request
	 * @param AuthenticationException|null $authException
	 *
	 * @return JsonResponse|Response
	 */
	public function start(Request $request, AuthenticationException $authException = null)
	{
		return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'access_token_invalid');
	}

	/**
	 * @return bool
	 */
	public function supportsRememberMe()
	{
		return false;
	}

}
