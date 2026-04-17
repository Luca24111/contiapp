<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryFormType;
use App\Service\CategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/categories')]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {
    }

    #[Route('', name: 'app_categories_index', methods: ['GET'])]
    public function index(): Response
    {
        $managementData = $this->categoryService->getManagementData();

        return $this->render('category/index.html.twig', [
            'categories' => $managementData['categories'],
            'overview' => $managementData['overview'],
            'createForm' => $this->createForm(CategoryFormType::class, new Category())->createView(),
        ]);
    }

    #[Route('/new', name: 'app_categories_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryService->createCategory($category);
            $this->addFlash('success', 'Categoria creata correttamente.');

            return $this->redirectToRoute('app_categories_index');
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categories_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $category = $this->categoryService->findCategory($id);
        if ($category === null) {
            throw $this->createNotFoundException('Categoria non trovata.');
        }

        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryService->updateCategory($category);
            $this->addFlash('success', 'Categoria aggiornata.');

            return $this->redirectToRoute('app_categories_index');
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_categories_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, Request $request): Response
    {
        $category = $this->categoryService->findCategory($id);
        if ($category === null) {
            throw $this->createNotFoundException('Categoria non trovata.');
        }

        if (!$this->isCsrfTokenValid('delete_category_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF non valido.');

            return $this->redirectToRoute('app_categories_index');
        }

        try {
            $this->categoryService->deleteCategory($category);
            $this->addFlash('success', 'Categoria eliminata.');
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_categories_index');
    }
}
