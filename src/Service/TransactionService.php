<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TransactionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TransactionRepository $transactionRepository,
    ) {
    }

    public function findTransaction(int $id): ?Transaction
    {
        return $this->transactionRepository->find($id);
    }

    public function createTransaction(Transaction $transaction): void
    {
        $this->synchronizeTypeWithCategory($transaction);
        $transaction->setCreatedAt(new \DateTimeImmutable());
        $transaction->touch();

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    public function updateTransaction(Transaction $transaction): void
    {
        $this->synchronizeTypeWithCategory($transaction);
        $transaction->touch();

        $this->entityManager->flush();
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        $this->entityManager->remove($transaction);
        $this->entityManager->flush();
    }

    /**
     * @return array{
     *     items: list<Transaction>,
     *     page: int,
     *     perPage: int,
     *     total: int,
     *     pages: int
     * }
     */
    public function getPaginatedTransactions(array $filters, int $page = 1, int $perPage = 12): array
    {
        $page = max(1, $page);
        $baseQb = $this->transactionRepository->createFilteredQueryBuilder($filters)
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('t.id', 'DESC');

        $total = count(new Paginator($baseQb));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $qb = clone $baseQb;
        $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new Paginator($qb);

        return [
            'items' => iterator_to_array($paginator->getIterator()),
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pages' => $pages,
        ];
    }

    public function getTotalByMonth(int $year, int $month, ?TransactionType $type = null): float
    {
        $start = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('first day of next month');

        $sql = <<<'SQL'
            SELECT COALESCE(SUM(amount), 0)
            FROM finance_transaction
            WHERE date >= :startDate AND date < :endDate
        SQL;

        $params = [
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
        ];

        if ($type !== null) {
            $sql .= ' AND type = :type';
            $params['type'] = $type->value;
        }

        return (float) $this->entityManager->getConnection()->fetchOne($sql, $params);
    }

    /**
     * @return array{income: float, expense: float, balance: float}
     */
    public function getMonthlySummary(int $year, int $month): array
    {
        $income = $this->getTotalByMonth($year, $month, TransactionType::INCOME);
        $expense = $this->getTotalByMonth($year, $month, TransactionType::EXPENSE);

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    public function getMonthlyBalance(int $year, int $month): float
    {
        $summary = $this->getMonthlySummary($year, $month);

        return $summary['balance'];
    }

    /**
     * @return array<int, array{name: string, color: string, total: float}>
     */
    public function getTotalByCategory(int $year, int $month, TransactionType $type = TransactionType::EXPENSE): array
    {
        $start = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('first day of next month');

        $sql = <<<'SQL'
            SELECT c.name, c.color, COALESCE(SUM(t.amount), 0) AS total
            FROM finance_transaction t
            INNER JOIN category c ON c.id = t.category_id
            WHERE t.date >= :startDate
              AND t.date < :endDate
              AND t.type = :type
            GROUP BY c.id, c.name, c.color
            ORDER BY total DESC, c.name ASC
        SQL;

        $rows = $this->entityManager->getConnection()->fetchAllAssociative($sql, [
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
            'type' => $type->value,
        ]);

        return array_map(
            static fn (array $row): array => [
                'name' => (string) $row['name'],
                'color' => (string) $row['color'],
                'total' => (float) $row['total'],
            ],
            $rows
        );
    }

    /**
     * @return array<int, array{label: string, year: int, month: int, income: float, expense: float, balance: float}>
     */
    public function getLastMonthsOverview(int $months = 6): array
    {
        $now = new \DateTimeImmutable('first day of this month');
        $start = $now->modify(sprintf('-%d months', max(0, $months - 1)));
        $results = [];

        for ($cursor = $start; $cursor <= $now; $cursor = $cursor->modify('+1 month')) {
            $year = (int) $cursor->format('Y');
            $month = (int) $cursor->format('m');
            $summary = $this->getMonthlySummary($year, $month);

            $results[] = [
                'label' => sprintf('%s %d', $this->getMonthLabel($month), $year),
                'year' => $year,
                'month' => $month,
                'income' => $summary['income'],
                'expense' => $summary['expense'],
                'balance' => $summary['balance'],
            ];
        }

        return $results;
    }

    /**
     * @return array<int, array{day: int, income: float, expense: float, balance: float}>
     */
    public function getDailyTotalsByMonth(int $year, int $month): array
    {
        $start = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('first day of next month');
        $daysInMonth = (int) $start->format('t');

        $sql = <<<'SQL'
            SELECT
                DAY(date) AS day_number,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense
            FROM finance_transaction
            WHERE date >= :startDate AND date < :endDate
            GROUP BY DAY(date)
            ORDER BY DAY(date) ASC
        SQL;

        $rows = $this->entityManager->getConnection()->fetchAllAssociative($sql, [
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
        ]);

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['day_number']] = [
                'income' => (float) $row['income'],
                'expense' => (float) $row['expense'],
            ];
        }

        $results = [];
        for ($day = 1; $day <= $daysInMonth; ++$day) {
            $income = $indexed[$day]['income'] ?? 0.0;
            $expense = $indexed[$day]['expense'] ?? 0.0;

            $results[] = [
                'day' => $day,
                'income' => $income,
                'expense' => $expense,
                'balance' => $income - $expense,
            ];
        }

        return $results;
    }

    /**
     * @return array<int, array{month: int, label: string, income: float, expense: float, balance: float}>
     */
    public function getAnnualTotals(int $year): array
    {
        $start = new \DateTimeImmutable(sprintf('%04d-01-01', $year));
        $end = $start->modify('first day of january next year');

        $sql = <<<'SQL'
            SELECT
                MONTH(date) AS month_number,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense
            FROM finance_transaction
            WHERE date >= :startDate AND date < :endDate
            GROUP BY MONTH(date)
            ORDER BY MONTH(date) ASC
        SQL;

        $rows = $this->entityManager->getConnection()->fetchAllAssociative($sql, [
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
        ]);

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['month_number']] = [
                'income' => (float) $row['income'],
                'expense' => (float) $row['expense'],
            ];
        }

        $results = [];
        for ($month = 1; $month <= 12; ++$month) {
            $income = $indexed[$month]['income'] ?? 0.0;
            $expense = $indexed[$month]['expense'] ?? 0.0;

            $results[] = [
                'month' => $month,
                'label' => $this->getMonthLabel($month),
                'income' => $income,
                'expense' => $expense,
                'balance' => $income - $expense,
            ];
        }

        return $results;
    }

    /**
     * @return array<int, array{month: int, label: string, balance: float}>
     */
    public function getAnnualCumulativeBalance(int $year): array
    {
        $cumulative = 0.0;
        $results = [];

        foreach ($this->getAnnualTotals($year) as $row) {
            $cumulative += $row['balance'];
            $results[] = [
                'month' => $row['month'],
                'label' => $row['label'],
                'balance' => $cumulative,
            ];
        }

        return $results;
    }

    /**
     * @return list<int>
     */
    public function getAvailableYears(): array
    {
        $years = $this->entityManager->getConnection()->fetchFirstColumn(
            'SELECT DISTINCT YEAR(date) AS year_number FROM finance_transaction ORDER BY year_number DESC'
        );

        if ($years === []) {
            return [(int) (new \DateTimeImmutable())->format('Y')];
        }

        return array_map(static fn (mixed $year): int => (int) $year, $years);
    }

    private function synchronizeTypeWithCategory(Transaction $transaction): void
    {
        if ($transaction->getCategory() !== null) {
            $transaction->setType($transaction->getCategory()->getType());
        }
    }

    private function getMonthLabel(int $month): string
    {
        return [
            1 => 'Gen',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mag',
            6 => 'Giu',
            7 => 'Lug',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Ott',
            11 => 'Nov',
            12 => 'Dic',
        ][$month];
    }
}
