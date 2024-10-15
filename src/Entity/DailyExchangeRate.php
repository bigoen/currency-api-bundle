<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle\Entity;

use Bigoen\CurrencyApiBundle\Repository\DailyExchangeRateRepository;
use Carbon\CarbonInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Table(name: 'exchange_rate_daily')]
#[ORM\Entity(repositoryClass: DailyExchangeRateRepository::class)]
#[UniqueEntity(fields: ['baseCurrency', 'currency', 'date'])]
final class DailyExchangeRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $baseCurrency = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $currency = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?CarbonInterface $date = null;

    #[ORM\Column]
    private ?float $value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseCurrency(): ?Currency
    {
        return $this->baseCurrency;
    }

    public function setBaseCurrency(?Currency $baseCurrency): static
    {
        $this->baseCurrency = $baseCurrency;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getDate(): ?CarbonInterface
    {
        return $this->date;
    }

    public function setDate(CarbonInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $this->value = $value;

        return $this;
    }
}
