<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * Find books by search term in title or author.
     */
    public function findBySearchTerm(string $searchTerm): array
    {
        dump($searchTerm);  // Dodaj ten wiersz

        $qb = $this->createQueryBuilder('b');
        $qb->where('b.title LIKE :searchTerm OR b.author LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%');

        return $qb->getQuery()->getResult();
    }

}
