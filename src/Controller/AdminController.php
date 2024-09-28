<?php

namespace App\Controller;

use App\Entity\Borrow;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\BorrowRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/admin_dashboard.html.twig');
    }

    #[Route('/admin/users', name: 'admin_user_list')]
    public function userList(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/user_list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/{id}/borrows', name: 'admin_borrow_list')]
    public function borrowList(User $user, BorrowRepository $borrowRepository): Response
    {
        $borrowedBooks = $borrowRepository->findBy(['user' => $user, 'returnedAt' => null]);

        return $this->render('admin/user_borrow_list.html.twig', [
            'user' => $user,
            'borrowedBooks' => $borrowedBooks,
        ]);
    }

    #[Route('/admin/borrow/return/{id}', name: 'admin_borrow_return')]
    public function returnBook(Borrow $borrow, EntityManagerInterface $entityManager): RedirectResponse
    {
        $borrow->markAsReturned();
        $entityManager->flush();

        return $this->redirectToRoute('admin_borrow_list');
    }

    #[Route('/admin/borrows/return-bulk', name: 'admin_borrow_return_bulk', methods: ['POST'])]
    public function returnBulkBooks(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $borrowIds = $request->request->all('borrow_ids');

        if (empty($borrowIds)) {
            return $this->redirectToRoute('admin_user_list');
        }

        $borrows = $entityManager->getRepository(Borrow::class)->findBy(['id' => $borrowIds]);

        foreach ($borrows as $borrow) {
            $borrow->markAsReturned();
        }

        $entityManager->flush();

        return $this->redirectToRoute('admin_borrow_list');
    }
}
