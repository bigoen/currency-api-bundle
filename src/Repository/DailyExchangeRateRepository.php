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
        // has not rates?
        if (0 === $this->count(['date' => $date])) {
            $this->service->updateDailyExchangeRates(date: $date);
        }
        // fromCurrency to USD.
        if ($fromCurrency !== 'USD') {
            try {
                $fromAmount = (float) $this->createQueryBuilder('dailyExchangeRate')
                    ->select('dailyExchangeRate.value')
                    ->innerJoin('dailyExchangeRate.currency', 'currency')
                    ->where('currency.code = :fromCurrency')
                    ->andWhere('dailyExchangeRate.date = :date')
                    ->setParameters([
                        'fromCurrency' => $fromCurrency,
                        'date' => $strDate,
                    ])
                    ->getQuery()
                    ->getSingleScalarResult();
            } catch (NoResultException|NonUniqueResultException) {
                throw new EntityNotFoundException('From currency not found.');
            }
        } else {
            $fromAmount = 1;
        }
        // USD to toCurrency.
        if ($toCurrency !== 'USD') {
            try {
                $toAmount = (float) $this->createQueryBuilder('dailyExchangeRate')
                    ->select('dailyExchangeRate.value')
                    ->innerJoin('dailyExchangeRate.currency', 'currency')
                    ->where('currency.code = :toCurrency')
                    ->andWhere('dailyExchangeRate.date = :date')
                    ->setParameters([
                        'toCurrency' => $toCurrency,
                        'date' => $strDate,
                    ])
                    ->getQuery()
                    ->getSingleScalarResult();
            } catch (NoResultException|NonUniqueResultException) {
                throw new EntityNotFoundException('To currency not found.');
            }
        } else {
            $toAmount = 1;
        }

        return ($amount / $fromAmount) * $toAmount;
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
