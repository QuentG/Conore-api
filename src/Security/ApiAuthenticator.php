<?php

namespace App\Security;

use App\Utils\Utils;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiAuthenticator extends AbstractGuardAuthenticator
{
	private const LOGIN_ROUTE = 'app_security_login';

	/** @var EntityManagerInterface */
	private $entityManager;
	/** @var Security */
	private $security;
	/** @var UserPasswordEncoderInterface */
	private $passwordEncoder;
	/** @var Utils */
	private $utils;
	/** @var ContainerInterface */
	private $containerInterface;

	/**
	 * ApiTokenAuthenticator constructor.
	 *
	 * @param EntityManagerInterface $entityManager
	 * @param Security $security
	 * @param UserPasswordEncoderInterface $passwordEncoder
	 * @param Utils $utils
	 * @param ContainerInterface $containerInterface
	 */
	public function __construct
	(
		EntityManagerInterface $entityManager,
		Security $security,
		UserPasswordEncoderInterface $passwordEncoder,
		Utils $utils,
		ContainerInterface $containerInterface
	)
	{
		$this->entityManager = $entityManager;
		$this->security = $security;
		$this->passwordEncoder = $passwordEncoder;
		$this->utils = $utils;
		$this->containerInterface = $containerInterface;
	}

	/**
	 * - has required body for authentication.
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function supports(Request $request)
    {
    	return self::LOGIN_ROUTE === $request->attributes->get('_route')
			&& $request->isMethod('POST');
    }

	/**
	 * @param Request $request
	 *
	 * @return array|mixed
	 */
    public function getCredentials(Request $request)
    {
		// Get body credentials
		$jsonData = \json_decode($request->getContent(), true);

		/** @var User $user */
		$user = $this->security->getUser();

    	if ($user && $user->getEmail() !== $jsonData['email']) {
            // Logout the user and continue the process
            $this->containerInterface->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();
        }

		return [
			'email' => $jsonData['email'],
			'password' => $jsonData['password']
		];
    }

	/**
	 * @param mixed $credentials
	 * @param UserProviderInterface $userProvider
	 *
	 * @return User|object|UserInterface|null
	 */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (!$credentials['email'] || !$credentials['password']) {
        	throw new AuthenticationException('credentials_was_empty');
		}

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
        	'email' => $credentials['email']
		]);

        if (is_null($user)) {
			throw new AuthenticationException('invalid_credentials');
		}

        return $user;
    }

	/**
	 * @param mixed $credentials
	 * @param UserInterface $user
	 *
	 * @return bool
	 */
    public function checkCredentials($credentials, UserInterface $user)
    {
    	return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
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
        return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'authentication_required');
    }

	/**
	 * @return bool
	 */
    public function supportsRememberMe()
    {
        return false;
    }

}
