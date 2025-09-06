<?php

namespace App\Repository;

use App\Entity\StatutParticipation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StatutParticipation>
 *
 * @method StatutParticipation|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatutParticipation|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatutParticipation[]    findAll()
 * @method StatutParticipation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatutParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatutParticipation::class);
    }

    public function save(StatutParticipation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(StatutParticipation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return StatutParticipation[] Returns an array of StatutParticipation objects
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
