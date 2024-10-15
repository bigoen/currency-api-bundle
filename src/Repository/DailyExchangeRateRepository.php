<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle\Repository;

use Bigoen\CurrencyApiBundle\Entity\DailyExchangeRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DailyExchangeRate>
 *
 * @method DailyExchangeRate|null find($id, $lockMode = null, $lockVersion = null)
 * @method DailyExchangeRate|null findOneBy(array $criteria, array $orderBy = null)
 * @method DailyExchangeRate[]    findAll()
 * @method DailyExchangeRate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DailyExchangeRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyExchangeRate::class);
    }

    //    /**
    //     * @return Daily[] Returns an array of DailyExchangeRate objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DailyExchangeRate
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
