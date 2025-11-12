<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 *
 * @method Service|null find($id, $lockMode = null, $lockVersion = null)
 * @method Service|null findOneBy(array $criteria, array $orderBy = null)
 * @method Service[]    findAll()
 * @method Service[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function save(Service $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Service $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllWithDirection(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.direction', 'd')
            ->addSelect('d')
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Top 5 des services les plus actifs par année (formations uniquement)
     */
    public function getTopActiveServicesByYear(int $year): array
    {
        // Ajouter les fonctions personnalisées pour travailler avec les dates
        $emConfig = $this->getEntityManager()->getConfiguration();
        $emConfig->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');
        
        $qb = $this->createQueryBuilder('s')
            ->select('s.libelle as service_name,
                     COUNT(DISTINCT fs.id) as formations_count,
                     COUNT(DISTINCT m.id) as missions_count,
                     (COUNT(DISTINCT fs.id) + COUNT(DISTINCT m.id)) as total_activities')
            ->leftJoin('s.direction', 'd')
            ->leftJoin('d.formationSessions', 'fs', 'WITH', 'YEAR(fs.datePrevueDebut) = :year')
            ->leftJoin('d.missions', 'm', 'WITH', 'YEAR(m.datePrevueDebut) = :year')
            ->setParameter('year', $year)
            ->groupBy('s.id', 's.libelle')
            ->having('(COUNT(DISTINCT fs.id) + COUNT(DISTINCT m.id)) > 0')
            ->orderBy('total_activities', 'DESC')
            ->setMaxResults(5);

        return $qb->getQuery()->getResult();
    }
}
