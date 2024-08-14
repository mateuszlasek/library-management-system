<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Borrow;
use App\Form\BookType;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin')]
class BookController extends AbstractController
{
    #[Route('/books', name: 'app_book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository): Response
    {
        return $this->render('admin/index.html.twig', [
            'books' => $bookRepository->findAll(),
        ]);
    }

    #[Route('/book/new', name: 'app_book_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the file.');
                }

                $book->setFileName($newFilename);
            }

            $entityManager->persist($book);
            $entityManager->flush();

            return $this->redirectToRoute('app_book_index');
        }

        return $this->render('book/new.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/book/{id}', name: 'app_book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_book_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/book/borrow/{id}', name: 'book_borrow')]
    public function borrow(Book $book, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->getUser();

        if ($book->getQuantity() < 1) {
            $this->addFlash('error', 'This book is not available for borrowing.');
            return $this->redirectToRoute('app_books_list');
        }

        if ($book->getStatus() === 'available') {
            $book->setStatus('reserved');
        } else {
            $book->setStatus('borrowed');
        }

        $borrow = new Borrow();
        $borrow->setBook($book);
        $borrow->setUser($user);
        $borrow->setBorrowedAt(new \DateTime());

        $book->setQuantity($book->getQuantity() - 1);

        $entityManager->persist($borrow);
        $entityManager->flush();

        $this->addFlash('success', 'Book borrowed successfully.');

        return $this->redirectToRoute('app_books_list');
    }
}
