<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle\Repository;

use Bigoen\CurrencyApiBundle\Entity\DailyExchangeRate;
use Bigoen\CurrencyApiBundle\Services\CurrencyBeaconService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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
    public function __construct(ManagerRegistry $registry, private readonly CurrencyBeaconService $service)
    {
        parent::__construct($registry, DailyExchangeRate::class);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws EntityNotFoundException
     */
    public function convert(string $fromCurrency, string $toCurrency, CarbonInterface $date, float $amount): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }
        // aliases.
        $strDate = $date->format('Y-m-d');
        // fromCurrency to USD.
        if ($fromCurrency !== 'USD') {
            try {
                // has not rates?
                if (!$this->has($fromCurrency, $date)) {
                    $this->service->updateDailyExchangeRates(date: $date);
                }
                $fromAmount = (float) $this->createQueryBuilder('dailyExchangeRate')
                    ->select('dailyExchangeRate.value')
                    ->innerJoin('dailyExchangeRate.currency', 'currency')
                    ->where('currency.code = :fromCurrency')
                    ->andWhere('dailyExchangeRate.date = :date')
                    ->setParameters([
                        'fromCurrency' => $fromCurrency,
                        'date' => $strDate,
                    ])
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getSingleScalarResult();
            } catch (NoResultException|NonUniqueResultException) {
                throw new EntityNotFoundException("From currency ($fromCurrency) not found for this $strDate date.");
            }
        } else {
            $fromAmount = 1;
        }
        // USD to toCurrency.
        if ($toCurrency !== 'USD') {
            try {
                // has not rates?
                if (!$this->has($toCurrency, $date)) {
                    $this->service->updateDailyExchangeRates(date: $date);
                }
                $toAmount = (float) $this->createQueryBuilder('dailyExchangeRate')
                    ->select('dailyExchangeRate.value')
                    ->innerJoin('dailyExchangeRate.currency', 'currency')
                    ->where('currency.code = :toCurrency')
                    ->andWhere('dailyExchangeRate.date = :date')
                    ->setParameters([
                        'toCurrency' => $toCurrency,
                        'date' => $strDate,
                    ])
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getSingleScalarResult();
            } catch (NoResultException|NonUniqueResultException) {
                throw new EntityNotFoundException("To currency ($toCurrency) not found for this $strDate date.");
            }
        } else {
            $toAmount = 1;
        }

        return ($amount / $fromAmount) * $toAmount;
    }

    public function deleteDuplicates(): int
    {
        // aliases.
        $total = 0;
        $tableName = $this->_em->getClassMetadata($this->getClassName())->getTableName();
        // find duplicate ids.
        $conn = $this->_em->getConnection();
        $sql = "
            SELECT
                entity.base_currency_id AS base_currency,
                entity.currency_id AS currency,
                entity.date AS date,
                ARRAY_TO_STRING(ARRAY(SELECT sub.id FROM $tableName sub WHERE sub.base_currency_id = entity.base_currency_id AND sub.currency_id = entity.currency_id AND sub.date = entity.date), ',') AS ids
            FROM
                $tableName entity
            GROUP BY base_currency, currency, date
            HAVING
                COUNT(entity.id) > 1
        ";
        $resultSet = $conn->executeQuery($sql);
        $data = $resultSet->fetchAllAssociative();
        // delete ids.
        foreach ($data as $value) {
            // has ids?
            if (!isset($value['ids'])) {
                continue;
            }
            $ids = explode(',', $value['ids']);
            foreach ($ids as $key => $id) {
                if (0 === $key) {
                    continue;
                }
                $this->createQueryBuilder('entity')
                    ->delete()
                    ->where('entity.id = :id')
                    ->setParameter('id', $id)
                    ->getQuery()
                    ->execute();
                ++$total;
            }
        }

        return $total;
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

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function has(string $currency, CarbonInterface $date): bool
    {
        return $this->createQueryBuilder('dailyExchangeRate')
            ->select('COUNT(dailyExchangeRate.id)')
            ->innerJoin('dailyExchangeRate.currency', 'currency')
            ->where('currency.code = :currency')
            ->andWhere('dailyExchangeRate.date = :date')
            ->setParameters([
                'currency' => $currency,
                'date' => $date->format('Y-m-d'),
            ])
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
