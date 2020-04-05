<?php

namespace App\Controller;

use App\Client\StripeClient;
use App\Entity\Club;
use App\Entity\User;
use App\Helper\StripeHelper;
use App\Helper\UserHelper;
use App\Manager\ClubManager;
use App\Manager\StripeManager;
use App\Manager\UserManager;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\EntityFieldEnum;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\OAuth\OAuthErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StripeController extends AbstractController
{
	/** @var string */
	private $stripeClientId;
	/** @var string */
	private $stripeClient;
	/** @var EntityManagerInterface */
	private $entityManager;
	/** @var Utils */
	private $utils;
	/** @var LoggerInterface */
	private $logger;
	/** @var ClubManager */
	private $clubManager;
	/** @var StripeManager */
	private $stripeManager;
	/** @var StripeHelper */
	private $stripeHelper;
	/** @var UserManager */
	private $userManager;
	private $userHelper;

	/**
	 * @param $stripeClientId
	 * @param StripeClient $stripeClient
	 * @param EntityManagerInterface $entityManager
	 * @param Utils $utils
	 * @param LoggerInterface $stripeLogger
	 * @param ClubManager $clubManager
	 * @param StripeManager $stripeManager
	 * @param StripeHelper $stripeHelper
	 * @param UserManager $userManager
	 * @param UserHelper $userHelper
	 */
	public function __construct
	(
		$stripeClientId,
		StripeClient $stripeClient,
		EntityManagerInterface $entityManager,
		Utils $utils,
		LoggerInterface $stripeLogger,
		ClubManager $clubManager,
		StripeManager $stripeManager,
		StripeHelper $stripeHelper,
		UserManager $userManager,
		UserHelper $userHelper
	)
	{
		$this->stripeClientId = $stripeClientId;
		$this->stripeClient = $stripeClient;
		$this->entityManager = $entityManager;
		$this->utils = $utils;
		$this->logger = $stripeLogger;
		$this->clubManager = $clubManager;
		$this->stripeManager = $stripeManager;
		$this->stripeHelper = $stripeHelper;
		$this->userManager = $userManager;
		$this->userHelper = $userHelper;
	}

	/**
	 * @return JsonResponse
	 */
	public function getStripeClientId()
	{
		if (empty($this->stripeClientId)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'empty_client_id');
		}

		/** @var User $user */
		$user = $this->getUser();

		if (!$user->isClubOwner()) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'no_club_owner');
		}

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $this->stripeClientId);
	}

	/**
	 * @param Request $request
	 * @param int $id
	 *
	 * @return JsonResponse
	 */
	public function authenticate(Request $request, int $id)
	{
		$jsonData = json_decode($request->getContent(), true);
		if (null === $jsonData) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
		}

		if (!array_key_exists(EntityFieldEnum::CODE_FIELD, $jsonData)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'missing_fields');
		}

		try {
			$response = $this->stripeClient->authenticationWithOauth($jsonData[EntityFieldEnum::CODE_FIELD]);
		} catch (OAuthErrorException $e) {
			$this->logger->error('Stripe error during auth => ' . $e->getMessage());
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'error_during_stripe_authentication');
		}

		$club = $this->entityManager->getRepository(Club::class)->find($id);
		if (!$club) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
		}

		$this->clubManager->addStripeInfos(
			$club,
			(string) $response->stripe_user_id,
			(string) $response->access_token
		);

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'stripe_account_connected');
	}

	/**
	 * @param Request $request
	 * @param int $id
	 *
	 * @return JsonResponse
	 */
	public function createProductPlan(Request $request, int $id)
	{
		$jsonData = json_decode($request->getContent(), true);
		if (null === $jsonData) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
		}

		$club = $this->entityManager->getRepository(Club::class)->find($id);
		if (!$club) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
		}

		$checkProduct = true;
		try {
			$this->stripeClient->checkProduct($jsonData[EntityFieldEnum::PRODUCT_NAME_FIELD], $club);
		} catch (ApiErrorException $e) {
			$this->logger->error('OK LE PRODUIT N\'EXISTE PAS');
			$checkProduct = false;
		}

		if (!$checkProduct) {
			$this->logger->error('ON CREE LE PRODUIT');
			try {
				$stripeProduct = $this->stripeClient->createProduct($club, $jsonData[EntityFieldEnum::PRODUCT_NAME_FIELD], $jsonData[EntityFieldEnum::DESCRIPTION_FIELD]);
			} catch (ApiErrorException $e) {
				$this->logger->error('Stripe error during product creation => ' . $e->getMessage());
				return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'error_during_stripe_creating_product');
			}

			$product = $this->stripeManager->createProduct($club, $stripeProduct->name, $stripeProduct->description);
		} else {
			$this->logger->error('ON RECUPERE LE PRODUIT');
			$product = $this->stripeHelper->retrieveProduct($jsonData[EntityFieldEnum::PRODUCT_NAME_FIELD], $club);
		}

		$checkPlan = true;
		$plan = null;
		try {
			$plan = $this->stripeClient->checkPlan($jsonData[EntityFieldEnum::PLAN_NAME_FIELD], $club);
		} catch (ApiErrorException $e) {
			$this->logger->error('OK ON VA CREE LE PLAN');
			$checkPlan = false;
		}

		if (!$checkPlan) {
			try {
				$plan = $this->stripeClient->createPlan($club, $jsonData[EntityFieldEnum::PLAN_NAME_FIELD], floatval($jsonData[EntityFieldEnum::AMOUNT_FIELD] * 100), $jsonData[EntityFieldEnum::INTERVAL_FIELD], $product);
			} catch (ApiErrorException $e) {
				$this->logger->error('Stripe error during plan creation => ' . $e->getMessage());
				return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'error_during_stripe_creating_plan');
			}

			$this->stripeManager->createPlan(
				(string) $plan->id,
				(float) $plan->amount_decimal,
				(string) $plan->interval,
				$product
			);
		} else {
			if ($plan !== null) {
				$this->stripeManager->createPlan(
					(string) $plan->id,
					(float) $plan->amount_decimal,
					(string) $plan->interval,
					$product
				);
			}
		}

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'stripe_product_plan_created');
	}

	/**
	 * @param int $id
	 *
	 * @return JsonResponse
	 */
	public function retrieveAllPlans(int $id)
	{
		$club = $this->entityManager->getRepository(Club::class)->find($id);
		if (!$club) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
		}

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $this->stripeManager->getAllPlans($club));
	}

	/**
	 * @param int $id
	 * @param string $name
	 *
	 * @return JsonResponse
	 */
	public function retrieveOnePlan(int $id, string $name)
	{
		$club = $this->entityManager->getRepository(Club::class)->find($id);
		if (!$club) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
		}

		$plan = $this->stripeHelper->findClubPlan($name, $club);
		if (!$plan) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'plan_not_found');
		}

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $this->stripeManager->getOnePlan($plan));
	}

	/**
	 * @param Request $request
	 * @param int $id
	 *
	 * @return JsonResponse
	 */
	public function createSubscription(Request $request, int $id)
	{
		$jsonData = json_decode($request->getContent(), true);
		if (null === $jsonData) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'incorrect_json');
		}

		$club = $this->entityManager->getRepository(Club::class)->find($id);
		if (!$club) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
		}

		/** @var User $user */
		$user = $this->getUser();

		// First of all we can check if customer already exist
		$customerAlreadyExist = true;

		if (!$user->getStripeCustomerId()) {
			$customerAlreadyExist = false;
		}

		if (!$customerAlreadyExist) {
			try {
				$customer = $this->stripeClient->createCustomer($user, $club);
			} catch (ApiErrorException $e) {
				$this->logger->error('Stripe error during customer creation => ' . $e->getMessage());
				return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'error_during_stripe_create_customer');
			}

			$this->userManager->addStripeInfos($user, $customer->id);
		}

		try {
			$this->stripeClient->createSource($user, $club, $jsonData[EntityFieldEnum::TOKEN_FIELD]);
		} catch (ApiErrorException $e) {
			$this->logger->error('Stripe error during source creation => ' . $e->getMessage());
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'error_during_stripe_create_source');
		}

		$plan = $this->stripeManager->getPlan($jsonData[EntityFieldEnum::PLAN_NAME_FIELD]);
		if (!$plan) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'plan_not_found');
		}

		try {
			$subscription = $this->stripeClient->createSubscription($club, $user->getStripeCustomerId(), $plan->getName());
		} catch (ApiErrorException $e) {
			$this->logger->error('Stripe error during subscription creation => ' . $e->getMessage());
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'error_during_stripe_create_subscription');
		}

		// Set other user's subscription for this club to inactive !
		$this->userManager->setInactiveSubscriptions($user, $club);
		// Create the new subscription for this club !
		$this->stripeManager->createSubscription($user, $plan->getName(), $subscription->current_period_start, $subscription->current_period_end, $plan->getAmount(), $plan->getPaymentInterval(), $club->getName());

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'subscription_created');
	}

	/**
	 * @param int $id
	 *
	 * @return JsonResponse
	 */
	public function getClubMoney(int $id)
	{
		$club = $this->entityManager->getRepository(Club::class)->find($id);
		if (!$club) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'club_not_found');
		}

		/** @var User $user */
		$user = $this->getUser();

		if (!$this->userHelper->isClubOwner($user, $club)) {
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'permission_not_authorized');
		}

		try {
			$balance = $this->stripeClient->getBalance($club);
		} catch (ApiErrorException $e) {
			$this->logger->error('Stripe error during retrieving balance => ' . $e->getMessage());
			return $this->utils->formatResponseApi(Response::HTTP_OK, 'error', 'error_during_stripe_get_balance');
		}

		$total = 0;

		foreach ($balance->pending as $item) {
			$total += $item->amount;
		}

		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', '', $total);
	}

}