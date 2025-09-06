<?php

namespace App\Repository;

use App\Entity\TypeFonds;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeFonds>
 *
 * @method TypeFonds|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeFonds|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeFonds[]    findAll()
 * @method TypeFonds[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeFondsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeFonds::class);
    }

    public function save(TypeFonds $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TypeFonds $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
