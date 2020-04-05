<?php

namespace App\Controller;

use App\Entity\Club;
use App\Entity\User;
use App\Helper\ClubHelper;
use App\Helper\UserHelper;
use App\Manager\ClubManager;
use App\Repository\ClubRepository;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use App\Enum\EntityFieldEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ClubController extends AbstractController
{
    /** @var ClubRepository|ObjectRepository */
    private $manager;
    /** @var ClubManager */
    private $clubManager;
    /** @var ClubHelper */
    private $clubHelper;
    /** @var UserHelper */
    private $userHelper;
	/** @var Utils */
	private $utils;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Utils $utils
     * @param ClubManager $clubManager
     * @param ClubHelper $clubHelper
     * @param UserHelper $userHelper
     */
    public function __construct(EntityManagerInterface $entityManager, ClubManager $clubManager, ClubHelper $clubHelper, UserHelper $userHelper, Utils $utils)
    {
        $this->manager = $entityManager->getRepository(Club::class);
        $this->clubManager = $clubManager;
        $this->clubHelper = $clubHelper;
        $this->userHelper = $userHelper;
		$this->utils = $utils;
	}

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createClub(Request $request)
    {
        $jsonData = \json_decode($request->getContent(), true);
        if (null === $jsonData) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
        }

        if (!array_key_exists(EntityFieldEnum::NAME_FIELD, $jsonData)) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'missing_fields');
        }

        $name = $jsonData[EntityFieldEnum::NAME_FIELD];
        /** @var User $user */
        $user = $this->getUser();

        if ($this->clubHelper->clubAlreadyExist($name, $user)) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_already_exist');
        }

        $this->clubManager->createClub($name, $user);

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'club_created');
    }

    /**
     * @return JsonResponse
     */
    public function getClubs()
    {
        $clubs = $this->manager->findAll();
        $tabClubs = [];

        /** @var User $user */
        $user = $this->getUser();

        foreach ($clubs as $club) {
            if (!$this->userHelper->isClubOwner($user, $club)) {
                continue;
            }

            $tabClubs[] = $this->clubHelper->getClubInfos($club);
        }

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $tabClubs);
    }

    /**
     * @param int $id
     *
     * @return JsonResponse
     */
    public function getClubById(int $id)
    {
        $club = $this->manager->find($id);
        if (!$club) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
        }

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $this->clubHelper->getClubInfos($club));
    }

    /**
     * @param int $id
     *
     * @return JsonResponse
     */
    public function getMembersClub(int $id)
    {
        $club = $this->manager->find($id);
        if (!$club) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
        }

		/** @var User $user */
		$user = $this->getUser();

		if (!$this->userHelper->isClubOwner($user, $club)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'permission_not_authorized');
		}

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $this->clubHelper->getClubInfos($club, true));
    }

    /**
     * @param int $id
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addMember(Request $request, int $id)
    {
        $jsonData = \json_decode($request->getContent(), true);
        if (null === $jsonData) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
        }

        if (!array_key_exists(EntityFieldEnum::EMAIL_FIELD, $jsonData)) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'missing_fields');
        }

        if ($this->userHelper->userAlreadyExist($jsonData[EntityFieldEnum::EMAIL_FIELD])) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'user_not_found');
        }

        $club = $this->manager->find($id);
        if (!$club) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
        }

		/** @var User $user */
		$user = $this->getUser();

		if (!$this->userHelper->isClubOwner($user, $club)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'permission_not_authorized');
		}

        if ($this->clubHelper->userIsAlreadyInClub($club, $jsonData[EntityFieldEnum::EMAIL_FIELD])) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'user_already_in_club');
        }

        $user = $this->userHelper->retrieveUser($jsonData[EntityFieldEnum::EMAIL_FIELD]);

        $this->clubManager->addMember($club, $user);

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'user_added');
    }

    /**
     * @param int $id
     *
     * @return JsonResponse
     */
    public function countMembersClub(int $id)
    {
        $club = $this->manager->find($id);
        if (!$club) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
        }

        /** @var User $user */
        $user = $this->getUser();

		if (!$this->userHelper->isClubOwner($user, $club)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'permission_not_authorized');
		}

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $club->getMembersCount());
    }

	/**
	 * @param int $id
	 *
	 * @return JsonResponse
	 */
	public function getNumberOfReservations(int $id)
	{
		$club = $this->manager->find($id);
		if (!$club) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
		}

		/** @var User $user */
		$user = $this->getUser();

		if (!$this->userHelper->isClubOwner($user, $club)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'permission_not_authorized');
		}

		$total = 0;
		foreach ($club->getSessions() as $session) {
			$total+= $session->countReservations();
		}

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $total);
	}

    /**
     * @param Request $request
     * @param int $id
     *
     * @return JsonResponse
     */
    public function editClub(Request $request, int $id)
    {
        $club = $this->manager->find($id);
        if (!$club) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
        }

        /** @var User $user */
        $user = $this->getUser();

		if (!$this->userHelper->isClubOwner($user, $club)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'permission_not_authorized');
		}

        $jsonData = \json_decode($request->getContent(), true);
        if (null === $jsonData) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
        }

        $this->clubManager->editClub($club, $jsonData);

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'club_modified');
    }

    /**
     * @param int $id
     *
     * @return JsonResponse
     */
    public function deleteClub(int $id)
    {
        $club = $this->manager->find($id);
        if (!$club) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
        }

		/** @var User $user */
		$user = $this->getUser();

		if (!$this->userHelper->isClubOwner($user, $club)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'permission_not_authorized');
		}

        if ($club->hasMembers()) {
            return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'members_in_club');
        }

        $this->clubManager->deleteClub($club);

        return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'club_deleted');
    }
}
