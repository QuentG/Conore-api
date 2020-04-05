<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

	/**
	 * @param int $id
	 * @param int $sessionId
	 *
	 * @return mixed
	 */
    public function getReservationsSessionById(int $id, int $sessionId)
	{
		return $this->createQueryBuilder('r')
			->innerJoin('r.session', 's')
			->innerJoin('s.club', 'c')
			->where('c.id = :id')
			->andWhere('s.id = :sessionId')
			->setParameters([
				'id' => $id,
				'sessionId' => $sessionId
			])
			->getQuery()
			->getResult()
		;
	}

	/**
	 * @param int $id
	 * @param int $sessionId
	 * @param int $userId
	 *
	 * @return mixed
	 *
	 * @throws NonUniqueResultException
	 */
	public function getUserReservationByClubAndSessionId(int $id, int $sessionId, int $userId)
	{
		return $this->createQueryBuilder('r')
			->innerJoin('r.session', 's')
			->innerJoin('s.club', 'c')
			->innerJoin('r.users', 'u')
			->where('c.id = :id')
			->andWhere('s.id = :sessionId')
			->andWhere('u.id = :userId')
			->setParameters([
				'id' => $id,
				'sessionId' => $sessionId,
				'userId' => $userId
			])
			->getQuery()
			->getOneOrNullResult()
		;
	}

	/**
	 * @param int $id
	 * @param int $sessionId
	 * @param int $reservationId
	 *
	 * @return mixed
	 *
	 * @throws NonUniqueResultException
	 */
	public function getReservationByClubAndSessionId(int $id, int $sessionId, int $reservationId)
	{
		return $this->createQueryBuilder('r')
			->innerJoin('r.session', 's')
			->innerJoin('s.club', 'c')
			->where('c.id = :id')
			->andWhere('s.id = :sessionId')
			->andWhere('r.id = :reservationId')
			->setParameters([
				'id' => $id,
				'sessionId' => $sessionId,
				'reservationId' => $reservationId
			])
			->getQuery()
			->getOneOrNullResult()
		;
	}

}
