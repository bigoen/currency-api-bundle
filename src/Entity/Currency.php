<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle\Entity;

use Bigoen\CurrencyApiBundle\Repository\CurrencyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'exchange_rate_currency')]
#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
#[UniqueEntity('code')]
final class Currency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 355)]
    #[ORM\Column(length: 355)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
