<?php

namespace App\Controller;

use App\Service\ChartDataService;
use App\Service\TransactionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reports')]
class ReportController extends AbstractController
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly ChartDataService $chartDataService,
    ) {
    }

    #[Route('/monthly/{year}/{month}', name: 'app_reports_monthly', requirements: ['year' => '\d{4}', 'month' => '\d{1,2}'], methods: ['GET'])]
    public function monthly(int $year, int $month): Response
    {
        if ($month < 1 || $month > 12) {
            throw $this->createNotFoundException('Mese non valido.');
        }

        $reference = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $previous = $reference->modify('-1 month');
        $next = $reference->modify('+1 month');

        return $this->render('report/monthly.html.twig', [
            'year' => $year,
            'month' => $month,
            'periodLabel' => $this->formatMonthLabel($month, $year),
            'summary' => $this->transactionService->getMonthlySummary($year, $month),
            'expenseDistributionChart' => $this->chartDataService->buildMonthlyExpenseDistribution($year, $month),
            'dailyTransactionsChart' => $this->chartDataService->buildMonthlyDailyBar($year, $month),
            'previousPeriod' => [
                'year' => (int) $previous->format('Y'),
                'month' => (int) $previous->format('m'),
            ],
            'nextPeriod' => [
                'year' => (int) $next->format('Y'),
                'month' => (int) $next->format('m'),
            ],
        ]);
    }

    #[Route('/annual/{year}', name: 'app_reports_annual', requirements: ['year' => '\d{4}'], methods: ['GET'])]
    public function annual(int $year): Response
    {
        $annualTotals = $this->transactionService->getAnnualTotals($year);
        $income = array_sum(array_column($annualTotals, 'income'));
        $expense = array_sum(array_column($annualTotals, 'expense'));

        return $this->render('report/annual.html.twig', [
            'year' => $year,
            'summary' => [
                'income' => $income,
                'expense' => $expense,
                'balance' => $income - $expense,
            ],
            'annualStackedChart' => $this->chartDataService->buildAnnualStacked($year),
            'annualCumulativeChart' => $this->chartDataService->buildAnnualCumulativeLine($year),
            'previousYear' => $year - 1,
            'nextYear' => $year + 1,
        ]);
    }

    private function formatMonthLabel(int $month, int $year): string
    {
        return sprintf('%s %d', [
            1 => 'Gennaio',
            2 => 'Febbraio',
            3 => 'Marzo',
            4 => 'Aprile',
            5 => 'Maggio',
            6 => 'Giugno',
            7 => 'Luglio',
            8 => 'Agosto',
            9 => 'Settembre',
            10 => 'Ottobre',
            11 => 'Novembre',
            12 => 'Dicembre',
        ][$month], $year);
    }
}
