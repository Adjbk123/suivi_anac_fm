<?php

namespace App\Repository;

use App\Entity\MissionSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MissionSession>
 *
 * @method MissionSession|null find($id, $lockMode = null, $lockVersion = null)
 * @method MissionSession|null findOneBy(array $criteria, array $orderBy = null)
 * @method MissionSession[]    findAll()
 * @method MissionSession[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MissionSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MissionSession::class);
    }

    public function save(MissionSession $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MissionSession $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    private function configureDqlFunctions(): void
    {
        $emConfig = $this->getEntityManager()->getConfiguration();
        $emConfig->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');
        $emConfig->addCustomDatetimeFunction('MONTH', 'DoctrineExtensions\Query\Mysql\Month');
    }

    public function countByYear(int $year): int
    {
        $this->configureDqlFunctions();

        return $this->createQueryBuilder('ms')
            ->select('COUNT(ms.id)')
            ->where('YEAR(ms.datePrevueDebut) = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countExecutedByYear(int $year): int
    {
        $this->configureDqlFunctions();

        return $this->createQueryBuilder('ms')
            ->select('COUNT(ms.id)')
            ->leftJoin('ms.statutActivite', 'sa')
            ->where('YEAR(ms.datePrevueDebut) = :year')
            ->andWhere('sa.code IN (:statuts)')
            ->setParameter('year', $year)
            ->setParameter('statuts', ['prevue_executee', 'non_prevue_executee'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPlannedByYear(int $year): int
    {
        $this->configureDqlFunctions();

        return $this->createQueryBuilder('ms')
            ->select('COUNT(ms.id)')
            ->leftJoin('ms.statutActivite', 'sa')
            ->where('YEAR(ms.datePrevueDebut) = :year')
            ->andWhere('sa.code = :statut')
            ->setParameter('year', $year)
            ->setParameter('statut', 'prevue_non_executee')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalBudgetByYear(int $year): float
    {
        $this->configureDqlFunctions();

        $result = $this->createQueryBuilder('ms')
            ->select('SUM(ms.budgetPrevu)')
            ->where('YEAR(ms.datePrevueDebut) = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getTotalRealExpensesByYear(int $year): float
    {
        $this->configureDqlFunctions();

        $result = $this->createQueryBuilder('ms')
            ->select('SUM(dm.montantReel)')
            ->leftJoin('ms.depenseMissions', 'dm')
            ->where('YEAR(ms.datePrevueDebut) = :year')
            ->andWhere('dm.montantReel IS NOT NULL')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getMonthlyStatsByYear(int $year): array
    {
        $this->configureDqlFunctions();

        $results = $this->createQueryBuilder('ms')
            ->select('MONTH(ms.datePrevueDebut) as month, COUNT(ms.id) as count')
            ->where('YEAR(ms.datePrevueDebut) = :year')
            ->setParameter('year', $year)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        $monthlyData = array_fill(1, 12, 0);
        foreach ($results as $result) {
            $monthlyData[$result['month']] = $result['count'];
        }

        return $monthlyData;
    }

    public function getStatusDistributionByYear(int $year): array
    {
        $this->configureDqlFunctions();

        $statuts = $this->getEntityManager()
            ->createQuery('SELECT sa FROM App\Entity\StatutActivite sa ORDER BY sa.libelle')
            ->getResult();

        $result = [];
        foreach ($statuts as $statut) {
            $count = $this->createQueryBuilder('ms')
                ->select('COUNT(ms.id)')
                ->where('YEAR(ms.datePrevueDebut) = :year')
                ->andWhere('ms.statutActivite = :statut')
                ->setParameter('year', $year)
                ->setParameter('statut', $statut)
                ->getQuery()
                ->getSingleScalarResult();

            $result[] = [
                'status' => $statut->getLibelle(),
                'count' => (int) $count,
            ];
        }

        return $result;
    }

    public function getTopExpensiveMissions(int $year, int $limit = 5): array
    {
        $this->configureDqlFunctions();

        return $this->createQueryBuilder('ms')
            ->leftJoin('ms.direction', 'd')
            ->leftJoin('ms.mission', 'm')
            ->select('m.titre, ms.budgetPrevu, d.libelle as direction_name')
            ->where('YEAR(ms.datePrevueDebut) = :year')
            ->andWhere('ms.budgetPrevu > 0')
            ->setParameter('year', $year)
            ->orderBy('ms.budgetPrevu', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getBottomExpensiveMissions(int $year, int $limit = 5): array
    {
        $this->configureDqlFunctions();

        return $this->createQueryBuilder('ms')
            ->leftJoin('ms.direction', 'd')
            ->leftJoin('ms.mission', 'm')
            ->select('m.titre, ms.budgetPrevu, d.libelle as direction_name')
            ->where('YEAR(ms.datePrevueDebut) = :year')
            ->andWhere('ms.budgetPrevu > 0')
            ->setParameter('year', $year)
            ->orderBy('ms.budgetPrevu', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getExecutionRateByDirection(int $year): array
    {
        $this->configureDqlFunctions();

        return $this->createQueryBuilder('ms')
            ->leftJoin('ms.direction', 'd')
            ->leftJoin('ms.statutActivite', 'sa')
            ->select('d.libelle as direction_name, COUNT(ms.id) as total,
                     SUM(CASE WHEN sa.code = :executed_code THEN 1 ELSE 0 END) as executed')
            ->where('YEAR(ms.datePrevueDebut) = :year')
            ->andWhere('d.libelle IS NOT NULL')
            ->setParameter('year', $year)
            ->setParameter('executed_code', 'prevue_executee')
            ->groupBy('d.libelle')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUserParticipationStats(int $year): array
    {
        $this->configureDqlFunctions();

        return $this->createQueryBuilder('ms')
            ->leftJoin('ms.userMissions', 'um')
            ->leftJoin('um.statutParticipation', 'sp')
            ->select('sp.libelle as status, COUNT(um.id) as count')
            ->where('YEAR(ms.datePrevueDebut) = :year')
            ->andWhere('sp.libelle IS NOT NULL')
            ->setParameter('year', $year)
            ->groupBy('sp.libelle')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAvailableYears(): array
    {
        $this->configureDqlFunctions();

        $results = $this->createQueryBuilder('ms')
            ->select('DISTINCT YEAR(ms.datePrevueDebut) as year')
            ->where('ms.datePrevueDebut IS NOT NULL')
            ->orderBy('year', 'DESC')
            ->getQuery()
            ->getResult();

        return array_column($results, 'year');
    }

    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('ms')
            ->leftJoin('ms.direction', 'd')
            ->leftJoin('ms.statutActivite', 'sa')
            ->leftJoin('ms.mission', 'm')
            ->addSelect('d', 'sa', 'm')
            ->orderBy('ms.datePrevueDebut', 'DESC');

        if (!empty($filters['direction'])) {
            $qb->andWhere('d.id = :direction')
                ->setParameter('direction', $filters['direction']);
        }

        if (!empty($filters['statut'])) {
            $qb->andWhere('sa.id = :statut')
                ->setParameter('statut', $filters['statut']);
        }

        if (!empty($filters['date_debut'])) {
            $qb->andWhere('ms.datePrevueDebut >= :date_debut')
                ->setParameter('date_debut', new \DateTime($filters['date_debut']));
        }

        if (!empty($filters['date_fin'])) {
            $qb->andWhere('ms.datePrevueFin <= :date_fin')
                ->setParameter('date_fin', new \DateTime($filters['date_fin']));
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('m.titre LIKE :search OR m.description LIKE :search OR d.libelle LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('ms')
            ->leftJoin('ms.direction', 'd')
            ->leftJoin('ms.statutActivite', 'sa')
            ->leftJoin('ms.fonds', 'f')
            ->leftJoin('ms.mission', 'm')
            ->addSelect('d', 'sa', 'f', 'm')
            ->orderBy('ms.datePrevueDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findExecutedSessions(): array
    {
        return $this->createQueryBuilder('ms')
            ->leftJoin('ms.direction', 'd')
            ->leftJoin('ms.statutActivite', 'sa')
            ->leftJoin('ms.fonds', 'f')
            ->leftJoin('ms.mission', 'm')
            ->addSelect('d', 'sa', 'f', 'm')
            ->where('sa.code IN (:statuts)')
            ->setParameter('statuts', ['prevue_executee', 'non_prevue_executee'])
            ->orderBy('ms.dateReelleDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPlannedSessions(): array
    {
        return $this->createQueryBuilder('ms')
            ->leftJoin('ms.direction', 'd')
            ->leftJoin('ms.statutActivite', 'sa')
            ->leftJoin('ms.fonds', 'f')
            ->leftJoin('ms.mission', 'm')
            ->addSelect('d', 'sa', 'f', 'm')
            ->where('sa.code = :statut')
            ->setParameter('statut', 'prevue_non_executee')
            ->orderBy('ms.datePrevueDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithFilters(?string $statutId = null, ?string $directionId = null, ?string $periode = null, ?string $participant = null): array
    {
        $this->configureDqlFunctions();

        $qb = $this->createQueryBuilder('ms')
            ->leftJoin('ms.direction', 'd')
            ->leftJoin('ms.statutActivite', 'sa')
            ->leftJoin('ms.fonds', 'f')
            ->leftJoin('ms.userMissions', 'um')
            ->leftJoin('um.user', 'u')
            ->leftJoin('ms.mission', 'm')
            ->addSelect('d', 'sa', 'f', 'um', 'u', 'm')
            ->orderBy('ms.datePrevueDebut', 'DESC');

        if ($statutId && $statutId !== '') {
            $qb->andWhere('sa.id = :statutId')
                ->setParameter('statutId', $statutId);
        }

        if ($directionId && $directionId !== '') {
            $qb->andWhere('d.id = :directionId')
                ->setParameter('directionId', $directionId);
        }

        if ($participant && $participant !== '') {
            $qb->andWhere('u.id = :participantId')
                ->setParameter('participantId', $participant);
        }

        if ($periode && $periode !== '') {
            $currentYear = date('Y');
            $currentMonth = date('n');

            switch ($periode) {
                case 'mois':
                    $qb->andWhere('YEAR(ms.datePrevueDebut) = :year AND MONTH(ms.datePrevueDebut) = :month')
                        ->setParameter('year', $currentYear)
                        ->setParameter('month', $currentMonth);
                    break;
                case 'trimestre':
                    $quarter = ceil($currentMonth / 3);
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $endMonth = $quarter * 3;
                    $qb->andWhere('YEAR(ms.datePrevueDebut) = :year AND MONTH(ms.datePrevueDebut) BETWEEN :startMonth AND :endMonth')
                        ->setParameter('year', $currentYear)
                        ->setParameter('startMonth', $startMonth)
                        ->setParameter('endMonth', $endMonth);
                    break;
                case 'annee':
                    $qb->andWhere('YEAR(ms.datePrevueDebut) = :year')
                        ->setParameter('year', $currentYear);
                    break;
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function findMissionSessions(int $missionId): array
    {
        return $this->createQueryBuilder('ms')
            ->leftJoin('ms.direction', 'd')
            ->leftJoin('ms.statutActivite', 'sa')
            ->addSelect('d', 'sa')
            ->where('ms.mission = :mission')
            ->setParameter('mission', $missionId)
            ->orderBy('ms.datePrevueDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchSuggestions(string $query): array
    {
        return $this->createQueryBuilder('ms')
            ->select('ms.id, m.titre, m.description, ms.datePrevueDebut, d.libelle as direction_name, sa.libelle as status_name')
            ->leftJoin('ms.mission', 'm')
            ->leftJoin('ms.direction', 'd')
            ->leftJoin('ms.statutActivite', 'sa')
            ->where('m.titre LIKE :query')
            ->orWhere('m.description LIKE :query')
            ->orWhere('d.libelle LIKE :query')
            ->orWhere('sa.libelle LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('ms.datePrevueDebut', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function searchGlobal(string $query): array
    {
        return $this->createQueryBuilder('ms')
            ->select('ms.id, m.titre, m.description, ms.datePrevueDebut, ms.budgetPrevu, d.libelle as direction_name, sa.libelle as status_name')
            ->leftJoin('ms.mission', 'm')
            ->leftJoin('ms.direction', 'd')
            ->leftJoin('ms.statutActivite', 'sa')
            ->where('m.titre LIKE :query')
            ->orWhere('m.description LIKE :query')
            ->orWhere('d.libelle LIKE :query')
            ->orWhere('sa.libelle LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('ms.datePrevueDebut', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }
}

