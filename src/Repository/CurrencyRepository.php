<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle\Repository;

use Bigoen\CurrencyApiBundle\Entity\Currency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Currency>
 *
 * @method Currency|null find($id, $lockMode = null, $lockVersion = null)
 * @method Currency|null findOneBy(array $criteria, array $orderBy = null)
 * @method Currency[]    findAll()
 * @method Currency[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Currency::class);
    }

    /**
     * @return array<string, Currency>
     */
    public function findIndexByCode(): array
    {
        return $this->createQueryBuilder('entity', 'entity.code')
            ->getQuery()
            ->getResult();
    }
}
