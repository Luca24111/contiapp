<?php

namespace App\Repository;

use App\Entity\Category;
use App\Enum\TransactionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return list<Category>
     */
    public function findByTypeOrdered(TransactionType $type): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.type = :type')
            ->setParameter('type', $type)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{category: Category, transactionCount: int}>
     */
    public function findWithTransactionCounts(?TransactionType $type = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.transactions', 't')
            ->addSelect('COUNT(t.id) AS transactionCount')
            ->groupBy('c.id')
            ->orderBy('c.type', 'ASC')
            ->addOrderBy('c.name', 'ASC');

        if ($type !== null) {
            $qb->andWhere('c.type = :type')->setParameter('type', $type);
        }

        $rows = $qb->getQuery()->getResult();

        return array_map(
            static fn (array $row): array => [
                'category' => $row[0],
                'transactionCount' => (int) $row['transactionCount'],
            ],
            $rows
        );
    }

    public function countTransactions(Category $category): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(t.id)')
            ->leftJoin('c.transactions', 't')
            ->andWhere('c = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
