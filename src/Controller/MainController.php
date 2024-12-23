<?php

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\BorrowRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }

    #[Route('/books', name: 'app_books_list')]
    public function books(BookRepository $bookRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $category = $request->query->get('category');

        if ($category) {
            $books = $bookRepository->findBy(['category' => $category], null, $limit, $offset);
            $totalBooks = count($bookRepository->findBy(['category' => $category]));
        } else {
            $books = $bookRepository->findBy([], null, $limit, $offset);
            $totalBooks = count($bookRepository->findAll());
        }

        $totalPages = ceil($totalBooks / $limit);

        $categories = $bookRepository->findDistinctCategories();

        return $this->render('book/books_list.html.twig', [
            'controller_name' => 'MainController',
            'books' => $books,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'categories' => $categories,
            'selectedCategory' => $category,
        ]);
    }


    #[Route('/my-account', name: 'app_account')]
    public function account(BorrowRepository $borrowRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $borrowedBooks = $borrowRepository->findBy(['user' => $user, 'returnedAt' => null]);

        $groupedBooks = [];
        foreach ($borrowedBooks as $borrow) {
            $bookId = $borrow->getBook()->getId();
            if (!isset($groupedBooks[$bookId])) {
                $groupedBooks[$bookId] = [
                    'book' => $borrow->getBook(),
                    'count' => 0,
                ];
            }
            $groupedBooks[$bookId]['count']++;
        }

        return $this->render('account/account.html.twig', [
            'controller_name' => 'MainController',
            'groupedBooks' => $groupedBooks,
        ]);
    }
}
