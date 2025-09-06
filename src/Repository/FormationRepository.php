<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 *
 * @method Formation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Formation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Formation[]    findAll()
 * @method Formation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    public function save(Formation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Formation $entity, bool $flush = false): void
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
     * Dernières formations créées
     */
    public function getRecentFormations(int $limit = 5): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.service', 's')
            ->leftJoin('f.statutActivite', 'sa')
            ->addSelect('s', 'sa')
            ->orderBy('f.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les formations par année
     */
    public function countByYear(int $year): int
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('YEAR(f.datePrevueDebut) = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les formations exécutées par année
     */
    public function countExecutedByYear(int $year): int
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->leftJoin('f.statutActivite', 'sa')
            ->where('YEAR(f.datePrevueDebut) = :year')
            ->andWhere('sa.code IN (:statuts)')
            ->setParameter('year', $year)
            ->setParameter('statuts', ['prevue_executee', 'non_prevue_executee'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les formations prévues par année
     */
    public function countPlannedByYear(int $year): int
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->leftJoin('f.statutActivite', 'sa')
            ->where('YEAR(f.datePrevueDebut) = :year')
            ->andWhere('sa.code = :statut')
            ->setParameter('year', $year)
            ->setParameter('statut', 'prevue_non_executee')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le budget total des formations par année
     */
    public function getTotalBudgetByYear(int $year): float
    {
        $this->configureDqlFunctions();
        
        $result = $this->createQueryBuilder('f')
            ->select('SUM(f.budgetPrevu)')
            ->where('YEAR(f.datePrevueDebut) = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ?? 0;
    }

    /**
     * Calcule les dépenses réelles totales des formations par année
     */
    public function getTotalRealExpensesByYear(int $year): float
    {
        $this->configureDqlFunctions();
        
        $result = $this->createQueryBuilder('f')
            ->select('SUM(d.montantReel)')
            ->leftJoin('f.depenseFormations', 'd')
            ->where('YEAR(f.datePrevueDebut) = :year')
            ->andWhere('d.montantReel IS NOT NULL')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ?? 0;
    }

    /**
     * Statistiques mensuelles des formations par année
     */
    public function getMonthlyStatsByYear(int $year): array
    {
        $this->configureDqlFunctions();
        
        $qb = $this->createQueryBuilder('f')
            ->select('MONTH(f.datePrevueDebut) as month, COUNT(f.id) as count')
            ->where('YEAR(f.datePrevueDebut) = :year')
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
     * Répartition des formations par statut pour une année
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
            $count = $this->createQueryBuilder('f')
                ->select('COUNT(f.id)')
                ->where('YEAR(f.datePrevueDebut) = :year')
                ->andWhere('f.statutActivite = :statut')
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
     * Top 5 des formations les plus coûteuses
     */
    public function getTopExpensiveFormations(int $year, int $limit = 5): array
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('f')
            ->leftJoin('f.service', 's')
            ->select('f.titre, f.budgetPrevu, s.libelle as service_name')
            ->where('YEAR(f.datePrevueDebut) = :year')
            ->andWhere('f.budgetPrevu > 0')
            ->setParameter('year', $year)
            ->orderBy('f.budgetPrevu', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Bottom 5 des formations les moins coûteuses
     */
    public function getBottomExpensiveFormations(int $year, int $limit = 5): array
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('f')
            ->select('f.titre, f.budgetPrevu, s.libelle as service_name')
            ->leftJoin('f.service', 's')
            ->where('YEAR(f.datePrevueDebut) = :year')
            ->andWhere('f.budgetPrevu > 0')
            ->setParameter('year', $year)
            ->orderBy('f.budgetPrevu', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Taux d'exécution des formations par direction
     */
    public function getExecutionRateByDirection(int $year): array
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('f')
            ->leftJoin('f.service', 's')
            ->leftJoin('s.direction', 'd')
            ->leftJoin('f.statutActivite', 'sa')
            ->select('d.libelle as direction_name, COUNT(f.id) as total, 
                     SUM(CASE WHEN sa.code = :executed_code THEN 1 ELSE 0 END) as executed')
            ->where('YEAR(f.datePrevueDebut) = :year')
            ->andWhere('d.libelle IS NOT NULL')
            ->setParameter('year', $year)
            ->setParameter('executed_code', 'prevue_executee')
            ->groupBy('d.libelle')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques de participation des utilisateurs aux formations
     */
    public function getUserParticipationStats(int $year): array
    {
        $this->configureDqlFunctions();
        
        return $this->createQueryBuilder('f')
            ->leftJoin('f.userFormations', 'uf')
            ->leftJoin('uf.statutParticipation', 'sp')
            ->select('sp.libelle as status, COUNT(uf.id) as count')
            ->where('YEAR(f.datePrevueDebut) = :year')
            ->andWhere('sp.libelle IS NOT NULL')
            ->setParameter('year', $year)
            ->groupBy('sp.libelle')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les années disponibles pour les formations
     */
    public function getAvailableYears(): array
    {
        $this->configureDqlFunctions();
        
        $qb = $this->createQueryBuilder('f')
            ->select('DISTINCT YEAR(f.datePrevueDebut) as year')
            ->where('f.datePrevueDebut IS NOT NULL')
            ->orderBy('year', 'DESC');
        
        $results = $qb->getQuery()->getResult();
        
        return array_column($results, 'year');
    }

    /**
     * Trouve les formations avec filtres
     */
    public function findFormationsWithFilters(int $year, int $month, array $criteria = []): array
    {
        $this->configureDqlFunctions();
        
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.service', 's')
            ->leftJoin('s.direction', 'd')
            ->leftJoin('f.statutActivite', 'sa')
            ->leftJoin('f.fonds', 'tf')
            ->leftJoin('f.userFormations', 'uf')
            ->where('YEAR(f.datePrevueDebut) = :year')
            ->andWhere('MONTH(f.datePrevueDebut) = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->orderBy('f.datePrevueDebut', 'DESC');
        
        // Appliquer les critères de filtrage
        if (!empty($criteria['service.direction.id'])) {
            $qb->andWhere('d.id = :directionId')
               ->setParameter('directionId', $criteria['service.direction.id']);
        }
        
        if (!empty($criteria['service.id'])) {
            $qb->andWhere('s.id = :serviceId')
               ->setParameter('serviceId', $criteria['service.id']);
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
            $qb->andWhere('f.lieuPrevu LIKE :lieu')
               ->setParameter('lieu', '%' . $criteria['lieuPrevu'] . '%');
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère toutes les formations avec leurs relations
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.service', 's')
            ->leftJoin('f.statutActivite', 'sa')
            ->leftJoin('f.fonds', 'fond')
            ->addSelect('s', 'sa', 'fond')
            ->orderBy('f.datePrevueDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les formations avec filtres
     */
    public function findAllWithFilters(?string $statutId = null, ?string $serviceId = null, ?string $periode = null, ?string $participant = null): array
    {
        $this->configureDqlFunctions();
        
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.service', 's')
            ->leftJoin('f.statutActivite', 'sa')
            ->leftJoin('f.fonds', 'fond')
            ->leftJoin('f.userFormations', 'uf')
            ->leftJoin('uf.user', 'u')
            ->addSelect('s', 'sa', 'fond', 'uf', 'u')
            ->orderBy('f.datePrevueDebut', 'DESC');
        
        // Filtre par statut
        if ($statutId && $statutId !== '') {
            $qb->andWhere('sa.id = :statutId')
               ->setParameter('statutId', $statutId);
        }
        
        // Filtre par service
        if ($serviceId && $serviceId !== '') {
            $qb->andWhere('s.id = :serviceId')
               ->setParameter('serviceId', $serviceId);
        }
        
        // Filtre par période
        if ($periode && $periode !== '') {
            $currentYear = date('Y');
            $currentMonth = date('n');
            
            switch ($periode) {
                case 'mois':
                    $qb->andWhere('YEAR(f.datePrevueDebut) = :year AND MONTH(f.datePrevueDebut) = :month')
                       ->setParameter('year', $currentYear)
                       ->setParameter('month', $currentMonth);
                    break;
                case 'trimestre':
                    $quarter = ceil($currentMonth / 3);
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $endMonth = $quarter * 3;
                    $qb->andWhere('YEAR(f.datePrevueDebut) = :year AND MONTH(f.datePrevueDebut) BETWEEN :startMonth AND :endMonth')
                       ->setParameter('year', $currentYear)
                       ->setParameter('startMonth', $startMonth)
                       ->setParameter('endMonth', $endMonth);
                    break;
                case 'annee':
                    $qb->andWhere('YEAR(f.datePrevueDebut) = :year')
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
     * Récupère les formations exécutées
     */
    public function findExecutedFormations(): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.service', 's')
            ->leftJoin('f.statutActivite', 'sa')
            ->leftJoin('f.fonds', 'fond')
            ->leftJoin('f.depenseFormations', 'df')
            ->addSelect('s', 'sa', 'fond', 'df')
            ->where('sa.code IN (:statuts)')
            ->setParameter('statuts', ['prevue_executee', 'non_prevue_executee'])
            ->orderBy('f.dateReelleDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les formations prévues
     */
    public function findPlannedFormations(): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.service', 's')
            ->leftJoin('f.statutActivite', 'sa')
            ->leftJoin('f.fonds', 'fond')
            ->leftJoin('f.userFormations', 'uf')
            ->addSelect('s', 'sa', 'fond', 'uf')
            ->where('sa.code = :statut')
            ->setParameter('statut', 'prevue_non_executee')
            ->orderBy('f.datePrevueDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche pour les suggestions d'autocomplétion
     */
    public function searchSuggestions(string $query): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select('f.id, f.titre, f.description, f.datePrevueDebut, s.libelle as service_name, sa.libelle as status_name')
            ->leftJoin('f.service', 's')
            ->leftJoin('f.statutActivite', 'sa')
            ->where('f.titre LIKE :query')
            ->orWhere('f.description LIKE :query')
            ->orWhere('s.libelle LIKE :query')
            ->orWhere('sa.libelle LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('f.datePrevueDebut', 'DESC')
            ->setMaxResults(5);
            
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Recherche globale dans les formations
     */
    public function searchGlobal(string $query): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select('f.id, f.titre, f.description, f.datePrevueDebut, f.budgetPrevu, s.libelle as service_name, sa.libelle as status_name')
            ->leftJoin('f.service', 's')
            ->leftJoin('f.statutActivite', 'sa')
            ->where('f.titre LIKE :query')
            ->orWhere('f.description LIKE :query')
            ->orWhere('s.libelle LIKE :query')
            ->orWhere('sa.libelle LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('f.datePrevueDebut', 'DESC')
            ->setMaxResults(20);
            
        return $qb->getQuery()->getResult();
    }
}
