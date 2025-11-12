<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.service', 's')
            ->leftJoin('u.domaine', 'd')
            ->addSelect('s', 'd')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Recherche pour les suggestions d'autocomplétion
     */
    public function searchSuggestions(string $query): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.id, u.nom, u.prenom, u.email, s.libelle as service_name, d.libelle as direction_name')
            ->leftJoin('u.service', 's')
            ->leftJoin('s.direction', 'd')
            ->where('u.nom LIKE :query')
            ->orWhere('u.prenom LIKE :query')
            ->orWhere('u.email LIKE :query')
            ->orWhere('s.libelle LIKE :query')
            ->orWhere('d.libelle LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.nom', 'ASC')
            ->setMaxResults(5);
            
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Recherche globale dans les utilisateurs
     */
    public function searchGlobal(string $query): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.id, u.nom, u.prenom, u.email, u.matricule, s.libelle as service_name, d.libelle as direction_name')
            ->leftJoin('u.service', 's')
            ->leftJoin('s.direction', 'd')
            ->where('u.nom LIKE :query')
            ->orWhere('u.prenom LIKE :query')
            ->orWhere('u.email LIKE :query')
            ->orWhere('u.matricule LIKE :query')
            ->orWhere('s.libelle LIKE :query')
            ->orWhere('d.libelle LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.nom', 'ASC')
            ->setMaxResults(20);
            
        return $qb->getQuery()->getResult();
    }

    /**
     * Trouver tous les utilisateurs d'une direction donnée
     * Prend en compte les utilisateurs avec direction directe OU via service
     */
    public function findByDirection(int $directionId): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.service', 's')
            ->leftJoin('s.direction', 'sd')
            ->where('u.direction = :directionId OR sd.id = :directionId')
            ->setParameter('directionId', $directionId)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC');
            
        return $qb->getQuery()->getResult();
    }

    public function findByDirectionPaginated(int $directionId, int $page, int $pageSize, array $excludedUserIds = []): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.service', 's')
            ->leftJoin('s.direction', 'sd')
            ->where('u.direction = :directionId OR sd.id = :directionId')
            ->setParameter('directionId', $directionId);
            
        if (!empty($excludedUserIds)) {
            $qb->andWhere('u.id NOT IN (:excludedIds)')
               ->setParameter('excludedIds', $excludedUserIds);
        }
        
        $qb->orderBy('u.nom', 'ASC')
           ->addOrderBy('u.prenom', 'ASC')
           ->setFirstResult(($page - 1) * $pageSize)
           ->setMaxResults($pageSize);

        return $qb->getQuery()->getResult();
    }
}
