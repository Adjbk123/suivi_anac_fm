<?php

namespace App\Repository;

use App\Entity\DepenseFormation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DepenseFormation>
 *
 * @method DepenseFormation|null find($id, $lockMode = null, $lockVersion = null)
 * @method DepenseFormation|null findOneBy(array $criteria, array $orderBy = null)
 * @method DepenseFormation[]    findAll()
 * @method DepenseFormation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepenseFormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepenseFormation::class);
    }

    public function save(DepenseFormation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DepenseFormation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
