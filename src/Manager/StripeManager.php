<?php

namespace App\Manager;

use App\Entity\Club;
use App\Entity\Plan;
use App\Entity\Product;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\PlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Psr\Log\LoggerInterface;

class StripeManager
{
	/** @var EntityManagerInterface */
	private $entityManager;
	/** @var PlanRepository|ObjectRepository */
	private $planRepository;
	/** @var LoggerInterface */
	private $stripeLogger;

	/**
	 * @param EntityManagerInterface $entityManager
	 * @param LoggerInterface $stripeLogger
	 */
	public function __construct(EntityManagerInterface $entityManager, LoggerInterface $stripeLogger)
	{
		$this->entityManager = $entityManager;
		$this->planRepository = $entityManager->getRepository(Plan::class);
		$this->stripeLogger = $stripeLogger;
	}

	/**
	 * @param Club $club
	 * @param string $name
	 * @param string $description
	 *
	 * @return Product
	 */
	public function createProduct(Club $club, string $name, string $description)
	{
		$product = new Product();
		$product->setName($name)
			->setDescription($description)
			->setClub($club);

		$this->entityManager->persist($product);
		$this->entityManager->flush();

		return $product;
	}

	/**
	 * @param string $name
	 * @param float $amount
	 * @param string $interval
	 * @param Product $product
	 *
	 * @return Plan
	 */
	public function createPlan(string $name, float $amount, string $interval, Product $product)
    {
        $plan = new Plan();
        $plan->setName($name)
			->setAmount($amount / 100)
			->setPaymentInterval($interval)
			->setProduct($product);

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        return $plan;
    }

	/**
	 * @param User $user
	 * @param string $name
	 * @param $startDate
	 * @param $endDate
	 * @param float $price
	 * @param string $frequency
	 * @param string $clubName
	 */
    public function createSubscription(User $user, string $name, $startDate, $endDate, float $price, string $frequency, string $clubName)
	{
		$subscription = new Subscription();
		$subscription->setName($name)
			->setStartDate((new \DateTime())->setTimestamp($startDate))
			->setEndDate((new \DateTime())->setTimestamp($endDate))
			->setPrice($price)
			->setFrequency($frequency)
			->setClubName($clubName)
			->addUser($user);

		$this->entityManager->persist($subscription);
		$this->entityManager->flush();
	}

	/**
	 * @param Club $club
	 *
	 * @return array
	 */
    public function getAllPlans(Club $club)
	{
		$tabPlans = [];

		if (!$club->hasProducts()) {
			return $tabPlans;
		}

		$this->stripeLogger->error('OK ON RECUP LES PLANS');

		/** @var Product $product */
		foreach ($club->getProducts() as $product) {
			if ($product->getPlan() === null) {
				$this->stripeLogger->error('PK T NUL ' . $product->getName());
				continue;
			}
			$tabPlans[] = [
				'name' => $product->getPlan()->getName(),
				'amount' => $product->getPlan()->getAmount(),
				'interval' => $product->getPlan()->getPaymentInterval(),
				'product' => $product->getName()
			];
		}

		return $tabPlans;
	}

	/**
	 * @param $plan
	 *
	 * @return array
	 */
	public function getOnePlan(Plan $plan)
	{
		return [
			'name' => $plan->getName(),
			'amount' => $plan->getAmount(),
			'interval' => $plan->getPaymentInterval(),
			'product' => $plan->getProduct()->getName()
		];
	}

	/**
	 * @param string $planName
	 *
	 * @return Plan|null
	 */
	public function getPlan(string $planName)
	{
		return $this->planRepository->findOneBy([
			'name' => $planName
		]);
	}

}