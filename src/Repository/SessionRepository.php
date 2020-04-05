<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Session|null find($id, $lockMode = null, $lockVersion = null)
 * @method Session|null findOneBy(array $criteria, array $orderBy = null)
 * @method Session[]    findAll()
 * @method Session[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

	/**
	 * @param int $id
	 * @param int $sessionId
	 *
	 * @return mixed
	 */
    public function getClubSessionById(int $id, int $sessionId)
	{
		try {
			return $this->createQueryBuilder('s')
				->innerJoin('s.club', 'c')
				->where('c.id = :id')
				->andWhere('s.id = :sessionId')
				->setParameters([
					'id' => $id,
					'sessionId' => $sessionId
				])
				->getQuery()
				->getOneOrNullResult()
			;
		} catch (NonUniqueResultException $e) {
			return null;
		}
	}

	/**
	 * @param int $id
	 * @param $datetime
	 *
	 * @return mixed
	 */
	public function getSessionsByDatetime(int $id, $datetime)
	{
		return $this->createQueryBuilder('s')
			->innerJoin('s.club', 'c')
			->where('c.id = :id')
			->andWhere('s.sessionAt BETWEEN :datetime AND :nextDay')
			->setParameters([
				'id' => $id,
				'datetime' => new \DateTime($datetime),
				'nextDay' => (new \DateTime($datetime))->add(new \DateInterval('P1D'))
			])
			->getQuery()
			->getResult()
		;
	}

}
