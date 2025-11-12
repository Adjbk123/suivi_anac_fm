<?php

namespace App\Repository;

use App\Entity\FormationSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationSession>
 *
 * @method FormationSession|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormationSession|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormationSession[]    findAll()
 * @method FormationSession[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormationSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationSession::class);
    }

    public function save(FormationSession $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FormationSession $entity, bool $flush = false): void
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
     * Dernières sessions de formation créées
     */
    public function getRecentSessions(int $limit = 5): array
    {
        return $this->createQueryBuilder('fs')
            ->leftJoin('fs.formation', 'f')
            ->leftJoin('fs.direction', 'd')
            ->leftJoin('fs.statutActivite', 'sa')
            ->addSelect('f', 'd', 'sa')
            ->orderBy('fs.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les sessions par année
     */
    public function countByYear(int $year): int
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('fs')
            ->select('COUNT(fs.id)')
            ->where('YEAR(fs.datePrevueDebut) = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les sessions exécutées par année
     */
    public function countExecutedByYear(int $year): int
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('fs')
            ->select('COUNT(fs.id)')
            ->leftJoin('fs.statutActivite', 'sa')
            ->where('YEAR(fs.datePrevueDebut) = :year')
            ->andWhere('sa.code IN (:statuts)')
            ->setParameter('year', $year)
            ->setParameter('statuts', ['prevue_executee', 'non_prevue_executee'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les sessions prévues par année
     */
    public function countPlannedByYear(int $year): int
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('fs')
            ->select('COUNT(fs.id)')
            ->leftJoin('fs.statutActivite', 'sa')
            ->where('YEAR(fs.datePrevueDebut) = :year')
            ->andWhere('sa.code = :statut')
            ->setParameter('year', $year)
            ->setParameter('statut', 'prevue_non_executee')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le budget total des sessions par année
     */
    public function getTotalBudgetByYear(int $year): float
    {
        $this->configureDqlFunctions();
        
        $result = $this->createQueryBuilder('fs')
            ->select('SUM(fs.budgetPrevu)')
            ->where('YEAR(fs.datePrevueDebut) = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ?? 0;
    }

    /**
     * Calcule les dépenses réelles totales des sessions par année
     */
    public function getTotalRealExpensesByYear(int $year): float
    {
        $this->configureDqlFunctions();
        
        $result = $this->createQueryBuilder('fs')
            ->select('SUM(d.montantReel)')
            ->leftJoin('fs.depenseFormations', 'd')
            ->where('YEAR(fs.datePrevueDebut) = :year')
            ->andWhere('d.montantReel IS NOT NULL')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ?? 0;
    }

    /**
     * Statistiques mensuelles des sessions par année
     */
    public function getMonthlyStatsByYear(int $year): array
    {
        $this->configureDqlFunctions();
        
        $qb = $this->createQueryBuilder('fs')
            ->select('MONTH(fs.datePrevueDebut) as month, COUNT(fs.id) as count')
            ->where('YEAR(fs.datePrevueDebut) = :year')
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
     * Répartition des sessions par statut pour une année
     */
    public function getStatusDistributionByYear(int $year): array
    {
        $this->configureDqlFunctions();
        
        $statuts = $this->getEntityManager()
            ->createQuery('SELECT sa FROM App\Entity\StatutActivite sa ORDER BY sa.libelle')
            ->getResult();
        
        $result = [];
        foreach ($statuts as $statut) {
            $count = $this->createQueryBuilder('fs')
                ->select('COUNT(fs.id)')
                ->where('YEAR(fs.datePrevueDebut) = :year')
                ->andWhere('fs.statutActivite = :statut')
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
     * Top 5 des sessions les plus coûteuses
     */
    public function getTopExpensiveSessions(int $year, int $limit = 5): array
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('fs')
            ->leftJoin('fs.formation', 'f')
            ->leftJoin('fs.direction', 'd')
            ->select('f.titre, fs.budgetPrevu, d.libelle as direction_name, fs.id')
            ->where('YEAR(fs.datePrevueDebut) = :year')
            ->andWhere('fs.budgetPrevu > 0')
            ->setParameter('year', $year)
            ->orderBy('fs.budgetPrevu', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Alias pour compatibilité avec l'ancien repository FormationRepository
     */
    public function getTopExpensiveFormations(int $year, int $limit = 5): array
    {
        return $this->getTopExpensiveSessions($year, $limit);
    }

    /**
     * Bottom 5 des sessions les moins coûteuses (budget > 0)
     */
    public function getBottomExpensiveSessions(int $year, int $limit = 5): array
    {
        $this->configureDqlFunctions();

        return $this->createQueryBuilder('fs')
            ->leftJoin('fs.formation', 'f')
            ->leftJoin('fs.direction', 'd')
            ->select('f.titre, fs.budgetPrevu, d.libelle as direction_name, fs.id')
            ->where('YEAR(fs.datePrevueDebut) = :year')
            ->andWhere('fs.budgetPrevu > 0')
            ->setParameter('year', $year)
            ->orderBy('fs.budgetPrevu', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Alias pour compatibilité avec l'ancien repository FormationRepository
     */
    public function getBottomExpensiveFormations(int $year, int $limit = 5): array
    {
        return $this->getBottomExpensiveSessions($year, $limit);
    }

    /**
     * Taux d'exécution des sessions par direction
     */
    public function getExecutionRateByDirection(int $year): array
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('fs')
            ->leftJoin('fs.direction', 'd')
            ->leftJoin('fs.statutActivite', 'sa')
            ->select('d.libelle as direction_name, COUNT(fs.id) as total, 
                     SUM(CASE WHEN sa.code = :executed_code THEN 1 ELSE 0 END) as executed')
            ->where('YEAR(fs.datePrevueDebut) = :year')
            ->andWhere('d.libelle IS NOT NULL')
            ->setParameter('year', $year)
            ->setParameter('executed_code', 'prevue_executee')
            ->groupBy('d.libelle')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques de participation des utilisateurs aux sessions
     */
    public function getUserParticipationStats(int $year): array
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('fs')
            ->leftJoin('fs.userFormations', 'uf')
            ->leftJoin('uf.statutParticipation', 'sp')
            ->select('sp.libelle as status, COUNT(uf.id) as count')
            ->where('YEAR(fs.datePrevueDebut) = :year')
            ->andWhere('sp.libelle IS NOT NULL')
            ->setParameter('year', $year)
            ->groupBy('sp.libelle')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les années disponibles pour les sessions
     */
    public function getAvailableYears(): array
    {
        $this->configureDqlFunctions();
        
        $qb = $this->createQueryBuilder('fs')
            ->select('DISTINCT YEAR(fs.datePrevueDebut) as year')
            ->where('fs.datePrevueDebut IS NOT NULL')
            ->orderBy('year', 'DESC');
        
        $results = $qb->getQuery()->getResult();
        
        return array_column($results, 'year');
    }

    /**
     * Récupère toutes les sessions avec leurs relations
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('fs')
            ->leftJoin('fs.formation', 'f')
            ->leftJoin('fs.direction', 'd')
            ->leftJoin('fs.statutActivite', 'sa')
            ->leftJoin('fs.fonds', 'fond')
            ->addSelect('f', 'd', 'sa', 'fond')
            ->orderBy('fs.datePrevueDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions avec filtres
     */
    public function findAllWithFilters(?string $statutId = null, ?string $directionId = null, ?string $periode = null, ?string $participant = null): array
    {
        $this->configureDqlFunctions();
        
        $qb = $this->createQueryBuilder('fs')
            ->leftJoin('fs.formation', 'f')
            ->leftJoin('fs.direction', 'd')
            ->leftJoin('fs.statutActivite', 'sa')
            ->leftJoin('fs.fonds', 'fond')
            ->leftJoin('fs.userFormations', 'uf')
            ->leftJoin('uf.user', 'u')
            ->addSelect('f', 'd', 'sa', 'fond', 'uf', 'u')
            ->orderBy('fs.datePrevueDebut', 'DESC');
        
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
                    $qb->andWhere('YEAR(fs.datePrevueDebut) = :year AND MONTH(fs.datePrevueDebut) = :month')
                       ->setParameter('year', $currentYear)
                       ->setParameter('month', $currentMonth);
                    break;
                case 'trimestre':
                    $quarter = ceil($currentMonth / 3);
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $endMonth = $quarter * 3;
                    $qb->andWhere('YEAR(fs.datePrevueDebut) = :year AND MONTH(fs.datePrevueDebut) BETWEEN :startMonth AND :endMonth')
                       ->setParameter('year', $currentYear)
                       ->setParameter('startMonth', $startMonth)
                       ->setParameter('endMonth', $endMonth);
                    break;
                case 'annee':
                    $qb->andWhere('YEAR(fs.datePrevueDebut) = :year')
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
     * Récupère les sessions exécutées
     */
    public function findExecutedSessions(): array
    {
        return $this->createQueryBuilder('fs')
            ->leftJoin('fs.formation', 'f')
            ->leftJoin('fs.direction', 'd')
            ->leftJoin('fs.statutActivite', 'sa')
            ->leftJoin('fs.fonds', 'fond')
            ->leftJoin('fs.depenseFormations', 'df')
            ->addSelect('f', 'd', 'sa', 'fond', 'df')
            ->where('sa.code IN (:statuts)')
            ->setParameter('statuts', ['prevue_executee', 'non_prevue_executee'])
            ->orderBy('fs.dateReelleDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sessions prévues
     */
    public function findPlannedSessions(): array
    {
        return $this->createQueryBuilder('fs')
            ->leftJoin('fs.formation', 'f')
            ->leftJoin('fs.direction', 'd')
            ->leftJoin('fs.statutActivite', 'sa')
            ->leftJoin('fs.fonds', 'fond')
            ->leftJoin('fs.userFormations', 'uf')
            ->addSelect('f', 'd', 'sa', 'fond', 'uf')
            ->where('sa.code = :statut')
            ->setParameter('statut', 'prevue_non_executee')
            ->orderBy('fs.datePrevueDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les sessions avec des filtres
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('fs')
            ->leftJoin('fs.formation', 'f')
            ->leftJoin('fs.direction', 'd')
            ->leftJoin('fs.statutActivite', 'sa')
            ->orderBy('fs.datePrevueDebut', 'DESC');

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
            $qb->andWhere('fs.datePrevueDebut >= :date_debut')
               ->setParameter('date_debut', new \DateTime($filters['date_debut']));
        }

        // Filtre par date de fin
        if (!empty($filters['date_fin'])) {
            $qb->andWhere('fs.datePrevueFin <= :date_fin')
               ->setParameter('date_fin', new \DateTime($filters['date_fin']));
        }

        // Filtre par recherche textuelle
        if (!empty($filters['search'])) {
            $qb->andWhere('f.titre LIKE :search OR f.description LIKE :search OR d.libelle LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les sessions d'une formation
     */
    public function findByFormation(int $formationId): array
    {
        return $this->createQueryBuilder('fs')
            ->leftJoin('fs.formation', 'f')
            ->leftJoin('fs.direction', 'd')
            ->leftJoin('fs.statutActivite', 'sa')
            ->addSelect('f', 'd', 'sa')
            ->where('f.id = :formationId')
            ->setParameter('formationId', $formationId)
            ->orderBy('fs.datePrevueDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche pour les suggestions d'autocomplétion
     */
    public function searchSuggestions(string $query): array
    {
        $qb = $this->createQueryBuilder('fs')
            ->leftJoin('fs.formation', 'f')
            ->select('fs.id, f.titre, f.description, fs.datePrevueDebut, d.libelle as direction_name, sa.libelle as status_name')
            ->leftJoin('fs.direction', 'd')
            ->leftJoin('fs.statutActivite', 'sa')
            ->where('f.titre LIKE :query')
            ->orWhere('f.description LIKE :query')
            ->orWhere('d.libelle LIKE :query')
            ->orWhere('sa.libelle LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('fs.datePrevueDebut', 'DESC')
            ->setMaxResults(5);
            
        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche globale dans les sessions
     */
    public function searchGlobal(string $query): array
    {
        $qb = $this->createQueryBuilder('fs')
            ->leftJoin('fs.formation', 'f')
            ->select('fs.id, f.titre, f.description, fs.datePrevueDebut, fs.budgetPrevu, d.libelle as direction_name, sa.libelle as status_name')
            ->leftJoin('fs.direction', 'd')
            ->leftJoin('fs.statutActivite', 'sa')
            ->where('f.titre LIKE :query')
            ->orWhere('f.description LIKE :query')
            ->orWhere('d.libelle LIKE :query')
            ->orWhere('sa.libelle LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('fs.datePrevueDebut', 'DESC')
            ->setMaxResults(20);
            
        return $qb->getQuery()->getResult();
    }
}
