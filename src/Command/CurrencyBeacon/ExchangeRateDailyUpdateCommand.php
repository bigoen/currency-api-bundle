<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle\Command\CurrencyBeacon;

use Bigoen\CurrencyApiBundle\Entity\DailyExchangeRate;
use Bigoen\CurrencyApiBundle\Services\CurrencyBeaconService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Doctrine\ORM\EntityManagerInterface;
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
    name: 'exchange-rate:currency-beacon:daily-update',
    description: 'Update exchange rate from https://currencybeacon.com.',
)]
final class ExchangeRateDailyUpdateCommand extends Command
{
    public function __construct(private readonly CurrencyBeaconService $service, private readonly EntityManagerInterface $entityManager)
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
        if ('latest' !== $type) {
            $oldDate = $this->entityManager->getRepository(DailyExchangeRate::class)->findOldDate();
            $endDate = $oldDate ?? Carbon::now();
            $datePeriod = array_reverse(CarbonPeriod::create($endDate->clone()->subYear(), $endDate)->toArray());
        } else {
            $datePeriod = [null];
        }
        foreach ($datePeriod as $date) {
            $this->service->updateDailyExchangeRates(date: $date);
            if ($date) {
                $io->success('Exchange rate updated for '.$date->format('Y-m-d'));
            }
        }
        $io->success('Exchange rate updated.');

        return Command::SUCCESS;
    }
}
