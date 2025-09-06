<?php

namespace App\Repository;

use App\Entity\StatutActivite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StatutActivite>
 *
 * @method StatutActivite|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatutActivite|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatutActivite[]    findAll()
 * @method StatutActivite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatutActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatutActivite::class);
    }

    public function save(StatutActivite $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(StatutActivite $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return StatutActivite[] Returns an array of StatutActivite objects
     */
    public function findByCode(string $code): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.code = :code')
            ->setParameter('code', $code)
            ->orderBy('s.libelle', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
