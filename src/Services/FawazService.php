<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle\Services;

use Bigoen\CurrencyApiBundle\Entity\Currency;
use Bigoen\CurrencyApiBundle\Entity\DailyExchangeRate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Symfony\Component\String\s;
use function Symfony\Component\String\u;

/**
 * @author Şafak Saylam <safak@bigoen.com>
 */
final readonly class FawazService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws HttpExceptionInterface
     */
    public function updateCurrencies(): void
    {
        $currencies = $this->getCurrencies();
        foreach ($currencies as $code => $currency) {
            $entity = (new Currency())
                ->setCode(u($code)->upper()->toString())
                ->setName($currency);
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
     * @throws HttpExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateDailyExchangeRates(string $code = 'USD', ?CarbonInterface $date = null): void
    {
        // aliases.
        $lowerCode = s($code)->lower()->toString();
        $currenciesWithCode = $this->entityManager->getRepository(Currency::class)->findIndexByCode();
        // get daily exchange rates.
        $dailyExchangeRates = $this->getDailyExchangeRates($lowerCode, $date);
        $date = Carbon::createFromFormat('Y-m-d', $dailyExchangeRates['date']);
        foreach ($dailyExchangeRates[$lowerCode] as $toCode => $value) {
            // alias.
            $toCode = s($toCode)->upper()->toString();
            // has not currencies.
            if (!isset($currenciesWithCode[$code], $currenciesWithCode[$toCode])) {
                continue;
            }
            // create.
            $entity = (new DailyExchangeRate())
                ->setBaseCurrency($currenciesWithCode[$code])
                ->setCurrency($currenciesWithCode[$toCode])
                ->setDate($date)
                ->setValue($value);
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
     * @throws HttpExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function getDailyExchangeRates(string $code, ?CarbonInterface $date = null): array
    {
        return $this->requestWithRetry(Request::METHOD_GET, "currencies/$code.json", $date);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws HttpExceptionInterface
     */
    private function getCurrencies(): array
    {
        return $this->requestWithRetry(Request::METHOD_GET, 'currencies.json');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws HttpExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function requestWithRetry(
        string $method,
        string $path,
        ?CarbonInterface $date = null,
        string $apiVersion = 'v1',
        array $options = []
    ): array {
        $maxRetries = 3;
        $attempts = 0;
        $arr = [];
        do {
            try {
                $arr = $this->httpClient->request($method, self::url($path, $date, $apiVersion, $attempts > 0), $options)->toArray();
                $exception = null;
            } catch (HttpExceptionInterface|TransportExceptionInterface|DecodingExceptionInterface $exception) {
                $attempts++;
                if ($attempts >= $maxRetries) {
                    throw $exception;
                }
                // Optional: Add a delay before retrying
                sleep(1);
            }
        } while ($exception);

        return $arr;
    }

    private static function url(string $path, ?CarbonInterface $date = null, string $apiVersion = 'v1', bool $isAlternative = false): string
    {
        $strDate = $date?->format('Y-m-d') ?? 'latest';

        return $isAlternative
            ? "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@$strDate/$apiVersion/$path"
            : "https://$strDate.currency-api.pages.dev/$apiVersion/$path";
    }
}