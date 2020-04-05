<?php

namespace App\Helper;

use App\Entity\Club;
use App\Entity\Plan;
use App\Entity\Product;
use App\Entity\Subscription;
use App\Repository\PlanRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class StripeHelper
{
	/** @var ProductRepository|ObjectRepository */
	private $productManager;
	/** @var PlanRepository|ObjectRepository */
	private $planManager;

	/**
	 * @param EntityManagerInterface $manager
	 */
	public function __construct(EntityManagerInterface $manager)
	{
		$this->productManager = $manager->getRepository(Product::class);
		$this->planManager = $manager->getRepository(Plan::class);
	}

	/**
	 * @param string $name
	 * @param Club $club
	 *
	 * @return bool
	 */
	public function findProduct(string $name, Club $club)
	{
		$product = $this->productManager->findOneBy([
			'name' => $name,
			'club' => $club
		]);

		return !is_null($product);
	}

	/**
	 * @param string $name
	 * @param Club $club
	 *
	 * @return Product
	 */
	public function retrieveProduct(string $name, Club $club)
	{
		return $this->productManager->findOneBy([
			'name' => $name,
			'club' => $club
		]);
	}

	/**
	 * @param string $name
	 * @param Club $club
	 *
	 * @return Plan|null
	 */
	public function findClubPlan(string $name, Club $club)
	{
		$plan = $this->planManager->findOneBy(['name' => $name]);

		if (!$plan || $plan->getProduct()->getClub()->getName() !== $club->getName()) {
			return null;
		}

		return $plan;
	}

	/**
	 * @param Subscription $subscription
	 * @param Club $club
	 *
	 * @return bool
	 */
	public function isClubSubscription(Subscription $subscription, Club $club)
	{
		return $subscription->getClubName() === $club->getName();
	}

}