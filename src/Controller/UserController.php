<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\UserHelper;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use App\Enum\EntityFieldEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    /** @var Utils */
    private $utils;
   	/** @var UserRepository|ObjectRepository */
    private $manager;
    /** @var UserHelper */
    private $userHelper;
    /** @var UserManager */
    private $userManager;

	/**
	 * @param Utils $utils
	 * @param EntityManagerInterface $entityManager
	 * @param UserHelper $userHelper
	 * @param UserManager $userManager
	 */
    public function __construct(Utils $utils, EntityManagerInterface $entityManager, UserHelper $userHelper, UserManager $userManager)
    {
        $this->utils = $utils;
        $this->manager = $entityManager->getRepository(User::class);
        $this->userHelper = $userHelper;
        $this->userManager = $userManager;
    }

    /**
     * @return JsonResponse
     */
    public function getMyAccount()
    {
    	/** @var User $user */
    	$user = $this->getUser();

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $this->userHelper->getUserInfos($user));
    }

	/**
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
    public function editUser(Request $request)
    {
        $jsonData = \json_decode($request->getContent(), true);
        if (null === $jsonData) {
        	return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
		}

        /** @var User $user */
        $user = $this->getUser();

		if (array_key_exists(EntityFieldEnum::EMAIL_FIELD, $jsonData)) {
			// Validate email format
			if (!$this->utils->validate($jsonData[EntityFieldEnum::EMAIL_FIELD])) {
				return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_email_format');
			}

			// Validate unique email
			if ($jsonData[EntityFieldEnum::EMAIL_FIELD] !== $user->getEmail()) {
				if ($this->userHelper->userAlreadyExist($jsonData[EntityFieldEnum::EMAIL_FIELD])) {
					return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'email_already_used');
				}
			}
		}

        $this->userManager->editUser($user, $jsonData);

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'user_infos_modified');
    }

    /**
     * @return JsonResponse
     */
	public function getUsers()
    {
    	if (!$this->isGranted('ROLE_ADMIN')) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'permission_not_authorized');
		}

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $this->userHelper->getAllUsers());
    }

    /**
     * @param int $id
     *
     * @return JsonResponse
     */
	public function getUserById(int $id)
    {
        $user = $this->manager->find($id);
        if (!$user) {
           return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'user_not_found');
        }

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $this->userHelper->getUserInfos($user));
    }

	/**
	 * @param int $id
	 *
	 * @return JsonResponse
	 */
	public function getUserClub(int $id)
	{
		$user = $this->manager->find($id);
		if (!$user) {
			return $this->utils->formatResponseApi(Response::HTTP_OK,'error', 'user_not_found');
		}

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success' , '', $this->userHelper->getUserOwnerClubs($user));
	}

}