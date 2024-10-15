<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle\Repository;

use Bigoen\CurrencyApiBundle\Entity\DailyExchangeRate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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

    public function findOldDate(): ?CarbonInterface
    {
        try {
            $data = $this->createQueryBuilder('dailyExchangeRate')
                ->select('dailyExchangeRate.date')
                ->orderBy('dailyExchangeRate.date', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
            $data = null;
        }

        return $data ? Carbon::createFromFormat('Y-m-d', $data) : null;
    }
}
