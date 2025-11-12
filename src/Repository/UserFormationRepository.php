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

    /**
     * Trouve les IDs des utilisateurs participants à une session de formation
     * (Méthode pour compatibilité avec l'ancien code - utilise formationSession maintenant)
     */
    public function findUserIdsByFormation(int $formationSessionId): array
    {
        $result = $this->createQueryBuilder('uf')
            ->select('IDENTITY(uf.user) as userId')
            ->where('uf.formationSession = :formationSessionId')
            ->setParameter('formationSessionId', $formationSessionId)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn ($row) => (int) $row['userId'], $result);
    }
    
    /**
     * Trouve les IDs des utilisateurs participants à une session de formation
     */
    public function findUserIdsByFormationSession(int $formationSessionId): array
    {
        return $this->findUserIdsByFormation($formationSessionId);
    }
}
