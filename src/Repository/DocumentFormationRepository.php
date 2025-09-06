<?php

namespace App\Repository;

use App\Entity\DocumentFormation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentFormation>
 *
 * @method DocumentFormation|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentFormation|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentFormation[]    findAll()
 * @method DocumentFormation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentFormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentFormation::class);
    }

    public function save(DocumentFormation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DocumentFormation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère tous les documents d'une formation
     */
    public function findByFormation(int $formationId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->orderBy('d.dateUpload', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
