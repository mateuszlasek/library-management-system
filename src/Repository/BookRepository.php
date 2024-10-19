<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function findAvailableBooks(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->setParameter('status', 'available')
            ->getQuery()
            ->getResult();
    }

    public function findBySearchTerm(string $searchTerm): array
    {
        $qb = $this->createQueryBuilder('b')
            ->where('b.title LIKE :searchTerm OR b.author LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%');

        return $qb->getQuery()->getResult();
    }

}
