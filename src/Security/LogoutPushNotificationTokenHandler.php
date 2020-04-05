<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutPushNotificationTokenHandler implements LogoutHandlerInterface
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
	 * @inheritDoc
	 */
	public function logout(Request $request, Response $response, TokenInterface $token)
	{
		/** @var User $user */
		$user = $token->getUser();

		if ($user instanceof User) {
			$user->setPushNotificationToken(null);
			$this->entityManager->flush();
		}
	}
}