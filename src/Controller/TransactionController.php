<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Form\TransactionFormType;
use App\Service\CategoryService;
use App\Service\TransactionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/transactions')]
class TransactionController extends AbstractController
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly CategoryService $categoryService,
    ) {
    }

    #[Route('', name: 'app_transactions_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $now = new \DateTimeImmutable();
        $filters = [
            'month' => (int) $request->query->get('month', $now->format('m')),
            'year' => (int) $request->query->get('year', $now->format('Y')),
            'category' => $request->query->get('category', ''),
            'type' => $request->query->get('type', ''),
        ];

        $page = max(1, $request->query->getInt('page', 1));

        return $this->render('transaction/index.html.twig', [
            'filters' => $filters,
            'pagination' => $this->transactionService->getPaginatedTransactions($filters, $page, 12),
            'categories' => $this->categoryService->getAllCategories(),
            'years' => $this->transactionService->getAvailableYears(),
        ]);
    }

    #[Route('/new', name: 'app_transactions_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $transaction = new Transaction();
        $categories = $this->categoryService->getAllCategories();
        $form = $this->createForm(TransactionFormType::class, $transaction, [
            'categories' => $categories,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->transactionService->createTransaction($transaction);
            $this->addFlash('success', 'Transazione creata correttamente.');

            return $this->redirectToRoute('app_transactions_index');
        }

        return $this->render('transaction/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_transactions_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $transaction = $this->transactionService->findTransaction($id);
        if ($transaction === null) {
            throw $this->createNotFoundException('Transazione non trovata.');
        }

        $form = $this->createForm(TransactionFormType::class, $transaction, [
            'categories' => $this->categoryService->getAllCategories(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->transactionService->updateTransaction($transaction);
            $this->addFlash('success', 'Transazione aggiornata.');

            return $this->redirectToRoute('app_transactions_index');
        }

        return $this->render('transaction/edit.html.twig', [
            'transaction' => $transaction,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_transactions_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, Request $request): Response
    {
        $transaction = $this->transactionService->findTransaction($id);
        if ($transaction === null) {
            throw $this->createNotFoundException('Transazione non trovata.');
        }

        if (!$this->isCsrfTokenValid('delete_transaction_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF non valido.');

            return $this->redirectToRoute('app_transactions_index');
        }

        $this->transactionService->deleteTransaction($transaction);
        $this->addFlash('success', 'Transazione eliminata.');

        return $this->redirectToRoute('app_transactions_index');
    }
}
