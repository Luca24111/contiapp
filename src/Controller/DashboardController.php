<?php

namespace App\Controller;

use App\Service\ChartDataService;
use App\Service\TransactionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly ChartDataService $chartDataService,
    ) {
    }

    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $now = new \DateTimeImmutable();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('m');

        $summary = $this->transactionService->getMonthlySummary($year, $month);
        $recentTransactions = $this->transactionService->getPaginatedTransactions([
            'year' => $year,
            'month' => $month,
        ], 1, 5);

        return $this->render('dashboard/index.html.twig', [
            'year' => $year,
            'month' => $month,
            'summary' => $summary,
            'recentTransactions' => $recentTransactions['items'],
            'monthlyComparisonChart' => $this->chartDataService->buildDashboardMonthlyComparison(),
            'expenseDistributionChart' => $this->chartDataService->buildDashboardExpenseDistribution($year, $month),
        ]);
    }
}
