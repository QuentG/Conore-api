<?php

namespace App\Client;

use App\Entity\Club;
use App\Entity\Product as ProductEntity;
use App\Entity\User;
use Stripe\Balance;
use Stripe\BankAccount;
use Stripe\Card;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\OAuth\OAuthErrorException;
use Stripe\OAuth;
use Stripe\Plan;
use Stripe\Product;
use Stripe\Source;
use Stripe\Stripe;
use Stripe\StripeObject;
use Stripe\Subscription;
use Stripe\SubscriptionSchedule;

class StripeClient
{
	/**
	 * @param $stripeSecretKey
	 */
	public function __construct($stripeSecretKey)
	{
		Stripe::setApiKey($stripeSecretKey);
	}

	/**
	 * @param $code
	 *
	 * @return StripeObject
	 *
	 * @throws OAuthErrorException
	 */
	public function authenticationWithOauth($code)
	{
		return OAuth::token([
			'grant_type' => 'authorization_code',
			'code' => $code
		]);
	}

	/**
	 * @param Club $club
	 * @param string $name
	 *
	 * @param string $description
	 * @return Product
	 *
	 * @throws ApiErrorException
	 */
	public function createProduct(Club $club, string $name, string $description)
	{
		return Product::create([
			'name' => $name,
			'description' => $description
		], ['stripe_account' => $club->getStripeAccountId(), 'api_key' => $club->getStripeAccessToken()]);
	}

	/**
	 * @param $name
	 * @param Club $club
	 *
	 * @return Product
	 *
	 * @throws ApiErrorException
	 */
	public function checkProduct($name, Club $club)
	{
		return Product::retrieve($name, ['stripe_account' => $club->getStripeAccountId(), 'api_key' => $club->getStripeAccessToken()]);
	}

	/**
	 * @param Club $club
	 * @param string $name
	 *
	 * @param float $amount
	 * @param string $interval
	 * @param ProductEntity $product
	 *
	 * @return Plan
	 *
	 * @throws ApiErrorException
	 */
	public function createPlan(Club $club, string $name, float $amount, string $interval, ProductEntity $product)
	{
		return Plan::create([
			'id' => $name,
			'amount_decimal' => $amount,
			'currency' => 'eur', // EU per default
			'interval' => $interval,
			'product' => [
				'name' => $product->getName()
			]
		], ['stripe_account' => $club->getStripeAccountId(), 'api_key' => $club->getStripeAccessToken()]);
	}

	/**
	 * @param $name
	 * @param Club $club
	 *
	 * @return Plan
	 *
	 * @throws ApiErrorException
	 */
	public function checkPlan($name, Club $club)
	{
		return Plan::retrieve($name, ['stripe_account' => $club->getStripeAccountId(), 'api_key' => $club->getStripeAccessToken()]);
	}

	/**
	 * @param User $user
	 * @param Club $club
	 *
	 * @return Customer
	 *
	 * @throws ApiErrorException
	 */
	public function createCustomer(User $user, Club $club)
	{
		return Customer::create([
			'email' => $user->getEmail(),
			'name' => $user->getFullName()
		], ["stripe_account" => $club->getStripeAccountId()]);
	}

	/**
	 * @param User $user
	 * @param Club $club
	 * @param string $token
	 *
	 * @return BankAccount|Card|Source
	 *
	 * @throws ApiErrorException
	 */
	public function createSource(User $user, Club $club, string $token)
	{
		return Customer::createSource(
			$user->getStripeCustomerId(),
			['source' => $token],
			["stripe_account" => $club->getStripeAccountId()]
		);
	}

	/**
	 * @param Club $club
	 * @param string $customerId
	 * @param string $plan
	 *
	 * @return Subscription
	 *
	 * @throws ApiErrorException
	 */
	public function createSubscription(Club $club, string $customerId, string $plan)
	{
		return Subscription::create([
			"customer" => $customerId,
			"items" => [
				["plan" => $plan],
			],
			'expand' => ['latest_invoice.payment_intent'],
			"application_fee_percent" => 10 // Get 10% on payment :D
		], ["stripe_account" => $club->getStripeAccountId()]);
	}

	/**
	 * @param Club $club
	 *
	 * @return Balance
	 *
	 * @throws ApiErrorException
	 */
	public function getBalance(Club $club)
	{
		return Balance::retrieve(['stripe_account' => $club->getStripeAccountId(), 'api_key' => $club->getStripeAccessToken()]);
	}

}