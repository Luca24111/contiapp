<?php

namespace App\Service;

use App\Entity\Category;
use App\Enum\TransactionType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class CategoryService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function findCategory(int $id): ?Category
    {
        return $this->categoryRepository->find($id);
    }

    /**
     * @return array{
     *     categories: array<int, array{category: Category, transactionCount: int}>,
     *     overview: array{
     *         total: int,
     *         income: int,
     *         expense: int,
     *         inUse: int,
     *         unused: int,
     *         linkedTransactions: int
     *     }
     * }
     */
    public function getManagementData(): array
    {
        $categories = $this->getCategoriesWithTransactionCounts();
        $income = 0;
        $expense = 0;
        $inUse = 0;
        $linkedTransactions = 0;

        foreach ($categories as $row) {
            if ($row['category']->getType() === TransactionType::INCOME) {
                ++$income;
            } else {
                ++$expense;
            }

            if ($row['transactionCount'] > 0) {
                ++$inUse;
            }

            $linkedTransactions += $row['transactionCount'];
        }

        return [
            'categories' => $categories,
            'overview' => [
                'total' => count($categories),
                'income' => $income,
                'expense' => $expense,
                'inUse' => $inUse,
                'unused' => count($categories) - $inUse,
                'linkedTransactions' => $linkedTransactions,
            ],
        ];
    }

    /**
     * @return array<int, array{category: Category, transactionCount: int}>
     */
    public function getCategoriesWithTransactionCounts(?TransactionType $type = null): array
    {
        return $this->categoryRepository->findWithTransactionCounts($type);
    }

    /**
     * @return list<Category>
     */
    public function getCategoriesByType(TransactionType $type): array
    {
        return $this->categoryRepository->findByTypeOrdered($type);
    }

    /**
     * @return list<Category>
     */
    public function getAllCategories(): array
    {
        return $this->categoryRepository->findBy([], ['type' => 'ASC', 'name' => 'ASC']);
    }

    public function createCategory(Category $category): void
    {
        $category->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }

    public function updateCategory(Category $category): void
    {
        $this->entityManager->flush();
    }

    public function canDelete(Category $category): bool
    {
        return $this->categoryRepository->countTransactions($category) === 0;
    }

    public function deleteCategory(Category $category): void
    {
        if (!$this->canDelete($category)) {
            throw new \LogicException('La categoria ha transazioni associate e non puo essere eliminata.');
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }
}
