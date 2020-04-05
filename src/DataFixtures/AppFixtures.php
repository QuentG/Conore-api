<?php

namespace App\DataFixtures;

use App\Entity\Club;
use App\Entity\Plan;
use App\Entity\Product;
use App\Entity\Reservation;
use App\Entity\Session;
use App\Entity\User;
use App\Manager\ApiTokenManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
	private $passwordEncoder;
	private $apiTokenManager;

	public function __construct(UserPasswordEncoderInterface $passwordEncoder, ApiTokenManager $apiTokenManager)
	{
		$this->passwordEncoder = $passwordEncoder;
		$this->apiTokenManager = $apiTokenManager;
	}

	public function load(ObjectManager $manager)
    {
    	$faker = Factory::create('fr_FR');

    	// CREATE OWNER
		$owner = new User();
		$owner->setEmail('test@test.com')
			->setUsername('LE BOSS')
			->setPassword($this->passwordEncoder->encodePassword($owner, 'test'))
			->setFirstConnection(false);

		$manager->persist($owner);

		$this->apiTokenManager->create($owner);

		$tabClub = [];

		// CREATE CLUB
		for ($c = 0; $c < 2; $c++) {
			$club = new Club();
			$club->setName($faker->company)
				->setCity($faker->city)
				->setAddress($faker->streetAddress)
				->setPhone($faker->phoneNumber)
				->setZipCode($faker->postcode)
				->setOwner($owner);

			$tabClub[] = $club;

			$manager->persist($club);
		}

		// CREATE CLIENT
		$tabClient = [];
		for ($i = 0; $i < 15; $i++) {
			$client = new User();
			$client->setEmail('test+'.$i.'@test.com')
				->setFirstName($faker->firstName)
				->setLastName($faker->lastName)
				->setUsername($faker->userName)
				->setPassword($this->passwordEncoder->encodePassword($client, 'test'));

			if ($i === 4) { // Client with 2 club
                $client->addClub($tabClub[0]);
                $client->addClub($tabClub[1]);
            } else {
                $client->addClub($faker->randomElement($tabClub));
            }

			$tabClient[] = $client;
			$manager->persist($client);

			$this->apiTokenManager->create($client);
		}

		$times = [30, 60];
		$places = [12, 16, 18, 20];
		$tabSession = [];
		// CREATE SESSION
		for ($s = 0; $s < 10; $s++) {
			$rnd = $faker->randomElement($places);

			$session = new Session();
			$session->setDuration($faker->randomElement($times))
				->setPlaces($rnd)
				->setClub($faker->randomElement($tabClub))
				->setSessionAt($faker->dateTimeBetween('now', '+7 days'));

			$tabSession[] = $session;
			$manager->persist($session);

		}
		$tabSessionFull = [];
		for ($sf = 0; $sf < 2; $sf++) {
			$sessionFull = new Session();
			$sessionFull->setDuration(30)
				->setPlaces(1)
				->setClub($tabClub[$sf])
				->setSessionAt($faker->dateTimeBetween('now', '+2 days'));

			$tabSessionFull[] = $sessionFull;
			$manager->persist($sessionFull);
		}

		for ($rf = 0; $rf < 2; $rf++) {
			$reservationForSessionFull = new Reservation();
			$reservationForSessionFull->setSession($tabSessionFull[$rf])
				->addUser($tabClient[5]);

			$manager->persist($reservationForSessionFull);
		}

		for ($r = 0; $r < 15; $r++) {
			$reservation = new Reservation();
			$reservation->addUser($faker->randomElement($tabClient))
				->setSession($faker->randomElement($tabSession));

			$manager->persist($reservation);
		}

		// Now we can flush
		$manager->flush();

    }
}
