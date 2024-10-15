<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle\Command\Fawaz;

use Bigoen\CurrencyApiBundle\Services\FawazService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'exchange-rate:fawaz:currency-update',
    description: 'Update currency from https://github.com/fawazahmed0/exchange-api?tab=readme-ov-file',
)]
final class CurrencyUpdateCommand extends Command
{
    public function __construct(private readonly FawazService $service)
    {
        parent::__construct();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->service->updateCurrencies();
        $io->success('Currency updated.');

        return Command::SUCCESS;
    }
}