<?php

namespace App\Tests;

use App\Entity\Category;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BlogPostTest extends WebTestCase
{
    public function testCreatePostFormAccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/posts/new');

        $this->assertResponseRedirects('/login');
    }

    public function testCreatePost(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneByEmail('admin@example.com');

        $client->loginUser($testUser);

        $imagePath = __DIR__ . '/assets/test-image.jpg';
        $image = new UploadedFile($imagePath, 'test-image.jpg', 'image/jpeg', null, true);

        $categoryRepository = static::getContainer()->get('doctrine')->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        $categoryIds = array_map(fn($category) => $category->getId(), $categories);

        $crawler = $client->request('GET', '/posts/new');
        $form = $crawler->selectButton('Create Post')->form([
            'post[title]' => 'Test Post with Image',
            'post[content]' => 'This is a test post with an image.',
            'post[image]' => $image,
            'post[categories]' => $categoryIds,
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/posts/show/1');

        $crawler = $client->followRedirect();

        $this->assertSelectorTextContains('.post-title', 'Test Post with Image');

        $image = $crawler->filter('.post-img')->first();
        $this->assertStringContainsString('uploads', $image->attr('src'));

        foreach ($categories as $category) {
            $this->assertSelectorTextContains('.post-categories', $category->getTitle());
        }
    }

    public function testCreatePostWithLargeImage(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneByEmail('admin@example.com');

        $client->loginUser($testUser);

        $largeImagePath = __DIR__ . '/assets/large-test-image.jpg';
        $largeImage = new UploadedFile($largeImagePath, 'large-test-image.jpg', 'image/jpeg', null, true);

        $categoryRepository = static::getContainer()->get('doctrine')->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        $categoryIds = array_map(fn($category) => $category->getId(), $categories);

        $crawler = $client->request('GET', '/posts/new');
        $form = $crawler->selectButton('Create Post')->form([
            'post[title]' => 'Test Post with Large Image',
            'post[content]' => 'This is a test post with an oversized image.',
            'post[image]' => $largeImage,
            'post[categories]' => $categoryIds,
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);

        $this->assertSelectorTextContains('.invalid-feedback', 'The file is too large');
    }
}