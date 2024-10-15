# Currency Api Bundle

This Symfony bundle provides integration with the Currency Beacon API, allowing you to fetch and manage currency data within your Symfony application.

## Installation

1. Install the bundle using Composer:

```bash
composer require bigoen/currency-api-bundle
```

2. Enable the bundle in your `config/bundles.php` file:

```php
return [
    // ...
    Bigoen\CurrencyApiBundle\BigoenCurrencyApiBundle::class => ['all' => true],
];
```

## Configuration

Add the following environment variables to your `.env` file:

```dotenv
###> bigoen/currency-api-bundle ###
CURRENCY_BEACON_API_KEY=your_api_key_here
###< bigoen/currency-api-bundle ###
```

## Usage

To use the Currency API service, you can inject it into your services or use it manually:

## Commands

This bundle provides commands to manage currency data:

- To update currencies, use:
    ```bash
    php bin/console exchange-rate:currency-beacon:currency-update
    ```

- To fetch the latest or historical daily exchange rates, use:
    ```bash
    php bin/console exchange-rate:currency-beacon:daily-update
    ```

Alternatively, you can use the `CurrencyBeaconService` directly in your code:

- To update currencies manually, use the `updateCurrencies` method:
    ```php
    $currencyBeaconService->updateCurrencies();
    ```

- To update daily exchange rates manually, use the `updateDailyExchangeRates` method:
    ```php
    $currencyBeaconService->updateDailyExchangeRates();
    ```

To query daily exchange rates by date and currency, you can use the `DailyExchangeRateRepository`:

- Example query:
    ```php
    $repository = $entityManager->getRepository(DailyExchangeRate::class);
    $exchangeRate = $repository->findOneBy(['date' => $date, 'currency' => $currency]);
    ```

## License

This bundle is released under the MIT License. See the bundled `LICENSE` file for details.