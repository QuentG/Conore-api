<?php

namespace App\Controller;

use App\Entity\User;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\EntityFieldEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PushNotificationController extends AbstractController
{
	/**
	 * @param Request $request
	 * @param EntityManagerInterface $manager
	 * @param Utils $utils
	 *
	 * @return JsonResponse
	 */
	public function new(Request $request, EntityManagerInterface $manager, Utils $utils)
	{
		$jsonData = \json_decode($request->getContent(), true);
		if (null === $jsonData) {
			return $utils->formatResponseApi(Response::HTTP_OK, 'error', 'malformatted_json');
		}

		if (!array_key_exists(EntityFieldEnum::TOKEN_FIELD, $jsonData) || !array_key_exists(EntityFieldEnum::PREVIOUS_TOKEN_FIELD, $jsonData)) {
			return $utils->formatResponseApi(Response::HTTP_OK, 'error', 'missing_fields');
		}

		if (empty($jsonData[EntityFieldEnum::TOKEN_FIELD])) {
			return $utils->formatResponseApi(Response::HTTP_OK, 'error', 'empty_fields');
		}

		/** @var User $user */
		$user = $this->getUser();

		if (null !== $user->getPushNotificationToken()) {
			if ($user->getPushNotificationToken() !== $jsonData[EntityFieldEnum::TOKEN_FIELD]) {
				$user->setPushNotificationToken($jsonData[EntityFieldEnum::TOKEN_FIELD]);
			}
		} else {
			$user->setPushNotificationToken($jsonData[EntityFieldEnum::TOKEN_FIELD]);
		}

		$manager->flush();

		return $utils->formatResponseApi(Response::HTTP_OK, 'success', 'push_token_saved');
	}
}