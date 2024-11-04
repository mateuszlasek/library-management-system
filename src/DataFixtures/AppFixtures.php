<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AppFixtures extends Fixture
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function load(ObjectManager $manager): void
    {
        $response = $this->httpClient->request(
            'GET',
            'https://www.googleapis.com/books/v1/volumes?q=programming&maxResults=10'
        );

        $booksData = $response->toArray();

        if (isset($booksData['items'])) {
            foreach ($booksData['items'] as $item) {
                $volumeInfo = $item['volumeInfo'];

                $book = new Book();
                $book->setTitle($volumeInfo['title'] ?? 'Unknown Title');
                $book->setAuthor(isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : 'Unknown Author');
                $book->setDescription($volumeInfo['description'] ?? 'No description available');
                $book->setCategory(isset($volumeInfo['categories']) ? implode(', ', $volumeInfo['categories']) : 'General');
                $book->setQuantity(rand(1, 10));

                $imageLinks = $volumeInfo['imageLinks'] ?? null;
                $thumbnailUrl = $imageLinks['thumbnail'] ?? null;

                if ($thumbnailUrl) {
                    try {
                        $imageResponse = $this->httpClient->request('GET', $thumbnailUrl);

                        if ($imageResponse->getStatusCode() === 200) {
                            $slugger = new AsciiSlugger();
                            $fileName = $slugger->slug($book->getTitle()) . '-' . uniqid() . '.jpg';

                            $imageContent = $imageResponse->getContent();
                            file_put_contents('assets/images/' . $fileName, $imageContent);

                            $book->setFileName($fileName);
                        }
                    } catch (\Exception $e) {
                    }
                }

                $manager->persist($book);
            }
        }

        $manager->flush();
    }
}
