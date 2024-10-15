<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle\Command\OpenSource;

use Bigoen\CurrencyApiBundle\Services\OpenSourceService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'exchange-rate:open-source:daily-update',
    description: 'Update exchange rate from https://github.com/fawazahmed0/exchange-api?tab=readme-ov-file.',
)]
final class ExchangeRateDailyUpdateCommand extends Command
{
    public function __construct(private readonly OpenSourceService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::OPTIONAL, 'Types: latest, historical', 'latest')
        ;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws HttpExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = $input->getArgument('type');
        $datePeriod = 'latest' !== $type
            ? CarbonPeriod::create(Carbon::createFromFormat('Y-m-d', '2024-04-01'), Carbon::now())
            : [Carbon::now()];
        foreach ($datePeriod as $date) {
            $this->service->updateDailyExchangeRates(date: $date);
            $io->success('Exchange rate updated for '.$date->format('Y-m-d'));
        }
        $io->success('Exchange rate updated.');

        return Command::SUCCESS;
    }
}
