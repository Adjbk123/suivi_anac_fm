<?php

namespace App\Repository;

use App\Entity\Mission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mission>
 *
 * @method Mission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Mission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Mission[]    findAll()
 * @method Mission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mission::class);
    }

    public function save(Mission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Mission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Configure les fonctions DQL personnalisées
     */
    private function configureDqlFunctions(): void
    {
        $emConfig = $this->getEntityManager()->getConfiguration();
        $emConfig->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');
        $emConfig->addCustomDatetimeFunction('MONTH', 'DoctrineExtensions\Query\Mysql\Month');
    }

    /**
     * Dernières missions créées
     */
    public function getRecentMissions(int $limit = 5): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.direction', 'd')
            ->leftJoin('m.statutActivite', 'sa')
            ->addSelect('d', 'sa')
            ->orderBy('m.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les missions par année
     */
    public function countByYear(int $year): int
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('YEAR(m.datePrevueDebut) = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les missions exécutées par année
     */
    public function countExecutedByYear(int $year): int
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.statutActivite', 'sa')
            ->where('YEAR(m.datePrevueDebut) = :year')
            ->andWhere('sa.code IN (:statuts)')
            ->setParameter('year', $year)
            ->setParameter('statuts', ['prevue_executee', 'non_prevue_executee'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les missions prévues par année
     */
    public function countPlannedByYear(int $year): int
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.statutActivite', 'sa')
            ->where('YEAR(m.datePrevueDebut) = :year')
            ->andWhere('sa.code = :statut')
            ->setParameter('year', $year)
            ->setParameter('statut', 'prevue_non_executee')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le budget total des missions par année
     */
    public function getTotalBudgetByYear(int $year): float
    {
        $this->configureDqlFunctions();
        
        $result = $this->createQueryBuilder('m')
            ->select('SUM(m.budgetPrevu)')
            ->where('YEAR(m.datePrevueDebut) = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ?? 0;
    }

    /**
     * Calcule les dépenses réelles totales des missions par année
     */
    public function getTotalRealExpensesByYear(int $year): float
    {
        $this->configureDqlFunctions();
        
        $result = $this->createQueryBuilder('m')
            ->select('SUM(d.montantReel)')
            ->leftJoin('m.depenseMissions', 'd')
            ->where('YEAR(m.datePrevueDebut) = :year')
            ->andWhere('d.montantReel IS NOT NULL')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ?? 0;
    }

    /**
     * Statistiques mensuelles des missions par année
     */
    public function getMonthlyStatsByYear(int $year): array
    {
        $this->configureDqlFunctions();
        
        $qb = $this->createQueryBuilder('m')
            ->select('MONTH(m.datePrevueDebut) as month, COUNT(m.id) as count')
            ->where('YEAR(m.datePrevueDebut) = :year')
            ->setParameter('year', $year)
            ->groupBy('month')
            ->orderBy('month', 'ASC');

        $results = $qb->getQuery()->getResult();
        
        $monthlyData = array_fill(1, 12, 0);
        foreach ($results as $result) {
            $monthlyData[$result['month']] = $result['count'];
        }
        
        return $monthlyData;
    }

    /**
     * Répartition des missions par statut pour une année
     */
    public function getStatusDistributionByYear(int $year): array
    {
        $this->configureDqlFunctions();
        
        // Récupérer tous les statuts d'activité
        $statuts = $this->getEntityManager()
            ->createQuery('SELECT sa FROM App\Entity\StatutActivite sa ORDER BY sa.libelle')
            ->getResult();
        
        $result = [];
        foreach ($statuts as $statut) {
            $count = $this->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->where('YEAR(m.datePrevueDebut) = :year')
                ->andWhere('m.statutActivite = :statut')
                ->setParameter('year', $year)
                ->setParameter('statut', $statut)
                ->getQuery()
                ->getSingleScalarResult();
            
            $result[] = [
                'status' => $statut->getLibelle(),
                'count' => (int) $count
            ];
        }
        
        return $result;
    }

    /**
     * Top 5 des missions les plus coûteuses
     */
    public function getTopExpensiveMissions(int $year, int $limit = 5): array
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('m')
            ->leftJoin('m.direction', 'd')
            ->select('m.titre, m.budgetPrevu, d.libelle as direction_name')
            ->where('YEAR(m.datePrevueDebut) = :year')
            ->andWhere('m.budgetPrevu > 0')
            ->setParameter('year', $year)
            ->orderBy('m.budgetPrevu', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Bottom 5 des missions les moins coûteuses
     */
    public function getBottomExpensiveMissions(int $year, int $limit = 5): array
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('m')
            ->leftJoin('m.direction', 'd')
            ->select('m.titre, m.budgetPrevu, d.libelle as direction_name')
            ->where('YEAR(m.datePrevueDebut) = :year')
            ->andWhere('m.budgetPrevu > 0')
            ->setParameter('year', $year)
            ->orderBy('m.budgetPrevu', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Taux d'exécution des missions par direction
     */
    public function getExecutionRateByDirection(int $year): array
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('m')
            ->leftJoin('m.direction', 'd')
            ->leftJoin('m.statutActivite', 'sa')
            ->select('d.libelle as direction_name, COUNT(m.id) as total, 
                     SUM(CASE WHEN sa.code = :executed_code THEN 1 ELSE 0 END) as executed')
            ->where('YEAR(m.datePrevueDebut) = :year')
            ->andWhere('d.libelle IS NOT NULL')
            ->setParameter('year', $year)
            ->setParameter('executed_code', 'prevue_executee')
            ->groupBy('d.libelle')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques de participation des utilisateurs aux missions
     */
    public function getUserParticipationStats(int $year): array
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('m')
            ->leftJoin('m.userMissions', 'um')
            ->leftJoin('um.statutParticipation', 'sp')
            ->select('sp.libelle as status, COUNT(um.id) as count')
            ->where('YEAR(m.datePrevueDebut) = :year')
            ->andWhere('sp.libelle IS NOT NULL')
            ->setParameter('year', $year)
            ->groupBy('sp.libelle')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les années disponibles pour les missions
     */
    public function getAvailableYears(): array
    {
        $this->configureDqlFunctions();
        
        $qb = $this->createQueryBuilder('m')
            ->select('DISTINCT YEAR(m.datePrevueDebut) as year')
            ->where('m.datePrevueDebut IS NOT NULL')
            ->orderBy('year', 'DESC');
        
        $results = $qb->getQuery()->getResult();
        
        return array_column($results, 'year');
    }

    /**
     * Trouve les missions avec filtres
     */
    public function findMissionsWithFilters(int $year, int $month, array $criteria = []): array
    {
        $this->configureDqlFunctions();
        
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.direction', 'd')
            ->leftJoin('m.statutActivite', 'sa')
            ->leftJoin('m.fonds', 'tf')
            ->leftJoin('m.userMissions', 'um')
            ->where('YEAR(m.datePrevueDebut) = :year')
            ->andWhere('MONTH(m.datePrevueDebut) = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->orderBy('m.datePrevueDebut', 'DESC');
        
        // Appliquer les critères de filtrage
        if (!empty($criteria['direction.id'])) {
            $qb->andWhere('d.id = :directionId')
               ->setParameter('directionId', $criteria['direction.id']);
        }
        
        if (!empty($criteria['statutActivite.id'])) {
            $qb->andWhere('sa.id = :statutId')
               ->setParameter('statutId', $criteria['statutActivite.id']);
        }
        
        if (!empty($criteria['fonds.id'])) {
            $qb->andWhere('tf.id = :fondsId')
               ->setParameter('fondsId', $criteria['fonds.id']);
        }
        
        if (!empty($criteria['lieuPrevu'])) {
            $qb->andWhere('m.lieuPrevu LIKE :lieu')
               ->setParameter('lieu', '%' . $criteria['lieuPrevu'] . '%');
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère toutes les missions avec leurs relations
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.direction', 'd')
            ->leftJoin('m.statutActivite', 'sa')
            ->leftJoin('m.fonds', 'f')
            ->addSelect('d', 'sa', 'f')
            ->orderBy('m.datePrevueDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les missions avec filtres
     */
    public function findAllWithFilters(?string $statutId = null, ?string $directionId = null, ?string $periode = null, ?string $participant = null): array
    {
        $this->configureDqlFunctions();
        
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.direction', 'd')
            ->leftJoin('m.statutActivite', 'sa')
            ->leftJoin('m.fonds', 'f')
            ->leftJoin('m.userMissions', 'um')
            ->leftJoin('um.user', 'u')
            ->addSelect('d', 'sa', 'f', 'um', 'u')
            ->orderBy('m.datePrevueDebut', 'DESC');
        
        // Filtre par statut
        if ($statutId && $statutId !== '') {
            $qb->andWhere('sa.id = :statutId')
               ->setParameter('statutId', $statutId);
        }
        
        // Filtre par direction
        if ($directionId && $directionId !== '') {
            $qb->andWhere('d.id = :directionId')
               ->setParameter('directionId', $directionId);
        }
        
        // Filtre par période
        if ($periode && $periode !== '') {
            $currentYear = date('Y');
            $currentMonth = date('n');
            
            switch ($periode) {
                case 'mois':
                    $qb->andWhere('YEAR(m.datePrevueDebut) = :year AND MONTH(m.datePrevueDebut) = :month')
                       ->setParameter('year', $currentYear)
                       ->setParameter('month', $currentMonth);
                    break;
                case 'trimestre':
                    $quarter = ceil($currentMonth / 3);
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $endMonth = $quarter * 3;
                    $qb->andWhere('YEAR(m.datePrevueDebut) = :year AND MONTH(m.datePrevueDebut) BETWEEN :startMonth AND :endMonth')
                       ->setParameter('year', $currentYear)
                       ->setParameter('startMonth', $startMonth)
                       ->setParameter('endMonth', $endMonth);
                    break;
                case 'annee':
                    $qb->andWhere('YEAR(m.datePrevueDebut) = :year')
                       ->setParameter('year', $currentYear);
                    break;
            }
        }
        
        // Filtre par participant
        if ($participant && $participant !== '') {
            $qb->andWhere('u.id = :participantId')
               ->setParameter('participantId', $participant);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les missions exécutées
     */
    public function findExecutedMissions(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.direction', 'd')
            ->leftJoin('m.statutActivite', 'sa')
            ->leftJoin('m.fonds', 'f')
            ->leftJoin('m.depenseMissions', 'dm')
            ->addSelect('d', 'sa', 'f', 'dm')
            ->where('sa.code IN (:statuts)')
            ->setParameter('statuts', ['prevue_executee', 'non_prevue_executee'])
            ->orderBy('m.dateReelleDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les missions prévues
     */
    public function findPlannedMissions(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.direction', 'd')
            ->leftJoin('m.statutActivite', 'sa')
            ->leftJoin('m.fonds', 'f')
            ->leftJoin('m.userMissions', 'um')
            ->addSelect('d', 'sa', 'f', 'um')
            ->where('sa.code = :statut')
            ->setParameter('statut', 'prevue_non_executee')
            ->orderBy('m.datePrevueDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche pour les suggestions d'autocomplétion
     */
    public function searchSuggestions(string $query): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select('m.id, m.titre, m.description, m.datePrevueDebut, d.libelle as direction_name, sa.libelle as status_name')
            ->leftJoin('m.direction', 'd')
            ->leftJoin('m.statutActivite', 'sa')
            ->where('m.titre LIKE :query')
            ->orWhere('m.description LIKE :query')
            ->orWhere('d.libelle LIKE :query')
            ->orWhere('sa.libelle LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('m.datePrevueDebut', 'DESC')
            ->setMaxResults(5);
            
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Recherche globale dans les missions
     */
    public function searchGlobal(string $query): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select('m.id, m.titre, m.description, m.datePrevueDebut, m.budgetPrevu, d.libelle as direction_name, sa.libelle as status_name')
            ->leftJoin('m.direction', 'd')
            ->leftJoin('m.statutActivite', 'sa')
            ->where('m.titre LIKE :query')
            ->orWhere('m.description LIKE :query')
            ->orWhere('d.libelle LIKE :query')
            ->orWhere('sa.libelle LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('m.datePrevueDebut', 'DESC')
            ->setMaxResults(20);
            
        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les missions avec des filtres
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.direction', 'd')
            ->leftJoin('m.statutActivite', 'sa')
            ->orderBy('m.datePrevueDebut', 'DESC');

        // Filtre par direction
        if (!empty($filters['direction'])) {
            $qb->andWhere('d.id = :direction')
               ->setParameter('direction', $filters['direction']);
        }

        // Filtre par statut
        if (!empty($filters['statut'])) {
            $qb->andWhere('sa.id = :statut')
               ->setParameter('statut', $filters['statut']);
        }

        // Filtre par date de début
        if (!empty($filters['date_debut'])) {
            $qb->andWhere('m.datePrevueDebut >= :date_debut')
               ->setParameter('date_debut', new \DateTime($filters['date_debut']));
        }

        // Filtre par date de fin
        if (!empty($filters['date_fin'])) {
            $qb->andWhere('m.datePrevueFin <= :date_fin')
               ->setParameter('date_fin', new \DateTime($filters['date_fin']));
        }

        // Filtre par recherche textuelle
        if (!empty($filters['search'])) {
            $qb->andWhere('m.titre LIKE :search OR m.description LIKE :search OR d.libelle LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
