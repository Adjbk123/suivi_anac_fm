<?php

namespace App\Repository;

use App\Entity\DepenseMission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DepenseMission>
 *
 * @method DepenseMission|null find($id, $lockMode = null, $lockVersion = null)
 * @method DepenseMission|null findOneBy(array $criteria, array $orderBy = null)
 * @method DepenseMission[]    findAll()
 * @method DepenseMission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepenseMissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepenseMission::class);
    }

    public function save(DepenseMission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DepenseMission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
