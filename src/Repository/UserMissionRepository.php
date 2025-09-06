<?php

namespace App\Repository;

use App\Entity\UserMission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserMission>
 *
 * @method UserMission|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserMission|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserMission[]    findAll()
 * @method UserMission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserMissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserMission::class);
    }

    public function save(UserMission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserMission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
