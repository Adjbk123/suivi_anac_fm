<?php

namespace App\Repository;

use App\Entity\DocumentMission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentMission>
 *
 * @method DocumentMission|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentMission|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentMission[]    findAll()
 * @method DocumentMission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentMissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentMission::class);
    }

    public function save(DocumentMission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DocumentMission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return DocumentMission[] Returns an array of DocumentMission objects
     */
    public function findByMission(int $missionId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.mission = :missionId')
            ->setParameter('missionId', $missionId)
            ->orderBy('d.dateUpload', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
