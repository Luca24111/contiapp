<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Enum\TransactionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function createFilteredQueryBuilder(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c');

        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', (int) $filters['category']);
        }

        $type = $this->resolveType($filters['type'] ?? null);
        if ($type !== null) {
            $qb->andWhere('t.type = :type')
                ->setParameter('type', $type);
        }

        $year = isset($filters['year']) && is_numeric($filters['year']) ? (int) $filters['year'] : null;
        $month = isset($filters['month']) && is_numeric($filters['month']) ? (int) $filters['month'] : null;

        if ($year !== null && $month !== null) {
            $start = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
            $end = $start->modify('first day of next month');

            $qb->andWhere('t.date >= :startDate')
                ->andWhere('t.date < :endDate')
                ->setParameter('startDate', $start)
                ->setParameter('endDate', $end);
        } elseif ($year !== null) {
            $start = new \DateTimeImmutable(sprintf('%04d-01-01', $year));
            $end = $start->modify('first day of january next year');

            $qb->andWhere('t.date >= :startDate')
                ->andWhere('t.date < :endDate')
                ->setParameter('startDate', $start)
                ->setParameter('endDate', $end);
        }

        return $qb;
    }

    private function resolveType(mixed $value): ?TransactionType
    {
        if ($value instanceof TransactionType) {
            return $value;
        }

        if (!is_string($value) || $value === '') {
            return null;
        }

        return TransactionType::tryFrom($value);
    }
}
