<?php

namespace App\Repository;

use App\Entity\Plan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Plan|null find($id, $lockMode = null, $lockVersion = null)
 * @method Plan|null findOneBy(array $criteria, array $orderBy = null)
 * @method Plan[]    findAll()
 * @method Plan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plan::class);
    }

	/**
	 * @param string $name
	 * @param int $productId
	 * @param int $clubId
	 *
	 * @return mixed|null
	 */
    public function checkProductPlanByClub(string $name, int $productId, int $clubId)
	{
		try {
			return $this->createQueryBuilder('p')
				->innerJoin('p.product', 'pr')
				->innerJoin('pr.club', 'c')
				->where('p.name = :name')
				->andWhere('pr.id = :productId')
				->andWhere('c.id = :clubId')
				->setParameters([
					'name' => $name,
					'productId' => $productId,
					'clubId' => $clubId
				])
				->getQuery()
				->getOneOrNullResult();
		} catch (NonUniqueResultException $e) {
			return null;
		}
	}

}
