<?php

namespace App\Repository;

use App\Entity\CategorieDepense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieDepense>
 *
 * @method CategorieDepense|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategorieDepense|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategorieDepense[]    findAll()
 * @method CategorieDepense[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategorieDepenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieDepense::class);
    }

    public function save(CategorieDepense $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CategorieDepense $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
