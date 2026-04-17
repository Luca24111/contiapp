<?php

namespace App\Service;

use App\Enum\TransactionType;

class ChartDataService
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {
    }

    public function buildDashboardMonthlyComparison(int $months = 6): array
    {
        $rows = $this->transactionService->getLastMonthsOverview($months);

        return [
            'labels' => array_column($rows, 'label'),
            'datasets' => [
                [
                    'label' => 'Entrate',
                    'data' => array_column($rows, 'income'),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.75)',
                    'borderColor' => '#22c55e',
                    'borderWidth' => 1,
                    'borderRadius' => 12,
                ],
                [
                    'label' => 'Uscite',
                    'data' => array_column($rows, 'expense'),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.75)',
                    'borderColor' => '#ef4444',
                    'borderWidth' => 1,
                    'borderRadius' => 12,
                ],
            ],
        ];
    }

    public function buildDashboardExpenseDistribution(int $year, int $month): array
    {
        return $this->buildCategoryDistributionData($year, $month, TransactionType::EXPENSE);
    }

    public function buildMonthlyExpenseDistribution(int $year, int $month): array
    {
        return $this->buildCategoryDistributionData($year, $month, TransactionType::EXPENSE);
    }

    public function buildMonthlyDailyBar(int $year, int $month): array
    {
        $rows = $this->transactionService->getDailyTotalsByMonth($year, $month);

        return [
            'labels' => array_map(static fn (array $row): string => (string) $row['day'], $rows),
            'datasets' => [
                [
                    'label' => 'Entrate giornaliere',
                    'data' => array_column($rows, 'income'),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.65)',
                    'borderColor' => '#22c55e',
                    'borderWidth' => 1,
                    'borderRadius' => 10,
                ],
                [
                    'label' => 'Uscite giornaliere',
                    'data' => array_column($rows, 'expense'),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.65)',
                    'borderColor' => '#ef4444',
                    'borderWidth' => 1,
                    'borderRadius' => 10,
                ],
            ],
        ];
    }

    public function buildAnnualStacked(int $year): array
    {
        $rows = $this->transactionService->getAnnualTotals($year);

        return [
            'labels' => array_column($rows, 'label'),
            'datasets' => [
                [
                    'label' => 'Entrate',
                    'data' => array_column($rows, 'income'),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => '#22c55e',
                    'borderWidth' => 1,
                    'stack' => 'monthly',
                ],
                [
                    'label' => 'Uscite',
                    'data' => array_column($rows, 'expense'),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => '#ef4444',
                    'borderWidth' => 1,
                    'stack' => 'monthly',
                ],
            ],
        ];
    }

    public function buildAnnualCumulativeLine(int $year): array
    {
        $rows = $this->transactionService->getAnnualCumulativeBalance($year);

        return [
            'labels' => array_column($rows, 'label'),
            'datasets' => [
                [
                    'label' => 'Saldo cumulativo',
                    'data' => array_column($rows, 'balance'),
                    'borderColor' => '#0f172a',
                    'backgroundColor' => 'rgba(15, 23, 42, 0.14)',
                    'borderWidth' => 3,
                    'pointBackgroundColor' => '#0f172a',
                    'pointRadius' => 4,
                    'tension' => 0.35,
                    'fill' => true,
                ],
            ],
        ];
    }

    private function buildCategoryDistributionData(int $year, int $month, TransactionType $type): array
    {
        $rows = $this->transactionService->getTotalByCategory($year, $month, $type);
        $referenceTotal = $this->transactionService->getTotalByMonth($year, $month, $type);

        if ($rows === []) {
            return [
                'labels' => ['Nessun dato'],
                'datasets' => [[
                    'label' => 'Nessun dato',
                    'data' => [1],
                    'backgroundColor' => ['#cbd5e1'],
                    'borderWidth' => 0,
                    'sharePercentages' => [0],
                    'referenceTotal' => $referenceTotal,
                ]],
            ];
        }

        return [
            'labels' => array_column($rows, 'name'),
            'datasets' => [[
                'label' => $type === TransactionType::INCOME ? 'Entrate per categoria' : 'Spese per categoria',
                'data' => array_column($rows, 'total'),
                'backgroundColor' => array_column($rows, 'color'),
                'borderWidth' => 0,
                'sharePercentages' => array_map(
                    static fn (array $row): float => $referenceTotal > 0
                        ? round(($row['total'] / $referenceTotal) * 100, 2)
                        : 0.0,
                    $rows
                ),
                'referenceTotal' => $referenceTotal,
            ]],
        ];
    }
}
