<?php

namespace App\Repository;

use App\Entity\UserFormation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFormation>
 *
 * @method UserFormation|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserFormation|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserFormation[]    findAll()
 * @method UserFormation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserFormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFormation::class);
    }

    public function save(UserFormation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserFormation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
