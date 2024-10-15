<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle\Services;

use Bigoen\CurrencyApiBundle\Entity\Currency;
use Bigoen\CurrencyApiBundle\Entity\DailyExchangeRate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Şafak Saylam <safak@bigoen.com>
 */
final class CurrencyBeaconService
{
    public const BASE_URI = 'https://api.currencybeacon.com/v1/';

    // Currency types.
    public const TYPE_FIAT = 'fiat';
    public const TYPE_CRYPTO = 'crypto';

    public function __construct(
        #[Autowire(env: 'CURRENCY_BEACON_API_KEY')] private readonly string $apiKey,
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateCurrencies(string $type = self::TYPE_FIAT): void
    {
        $currencies = $this->getCurrencies($type);
        foreach ($currencies as $currency) {
            $entity = (new Currency())
                ->setCode($currency['short_code'])
                ->setName($currency['name']);
            if (0 === $this->validator->validate($entity)->count()) {
                $this->entityManager->persist($entity);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function updateDailyExchangeRates(string $base = 'USD', ?CarbonInterface $date = null): void
    {
        // aliases.
        $currenciesWithCode = $this->entityManager->getRepository(Currency::class)->findIndexByCode();
        // get daily exchange rates.
        if ($date) {
            $exchange = $this->getHistorical($base, $date);
        } else {
            $exchange = $this->getLatest($base);
            $date = Carbon::now();
        }
        $baseCurrency = $currenciesWithCode[$base] ?? throw new \InvalidArgumentException('Invalid base currency.');
        if (!isset($exchange['rates'], $exchange['date'], $exchange['base'])) {
            throw new \RuntimeException('Invalid response.');
        }
        foreach ($exchange['rates'] as $rateCurrency => $rate) {
            // has not currency?
            if (!isset($currenciesWithCode[$rateCurrency])) {
                continue;
            }
            // create.
            $entity = (new DailyExchangeRate())
                ->setBaseCurrency($baseCurrency)
                ->setCurrency($currenciesWithCode[$rateCurrency])
                ->setDate($date)
                ->setValue($rate);
            // validate.
            if (0 === $this->validator->validate($entity)->count()) {
                $this->entityManager->persist($entity);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function getLatest(string $base): array
    {
        return $this->httpClient->request(Request::METHOD_GET, 'latest', [
            'base_uri' => self::BASE_URI,
            'query' => [
                'api_key' => $this->apiKey,
                'base' => $base,
            ],
        ])->toArray()['response'] ?? [];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function getHistorical(string $base, CarbonInterface $date): array
    {
        return $this->httpClient->request(Request::METHOD_GET, 'historical', [
            'base_uri' => self::BASE_URI,
            'query' => [
                'api_key' => $this->apiKey,
                'base' => $base,
                'date' => $date->format('Y-m-d'),
            ],
        ])->toArray()['response'] ?? [];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function getCurrencies(string $type = self::TYPE_FIAT): array
    {
        return $this->httpClient->request(Request::METHOD_GET, 'currencies', [
            'base_uri' => self::BASE_URI,
            'query' => [
                'api_key' => $this->apiKey,
                'type' => $type,
            ],
        ])->toArray()['response'];
    }
}