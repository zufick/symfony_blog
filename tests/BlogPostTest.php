<?php

namespace App\Tests;

use App\Entity\Category;
use App\Entity\Post;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BlogPostTest extends WebTestCase
{
    public function testCreatePostFormAccessGuest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/posts/new');

        $this->assertResponseRedirects('/login');
    }

    public function testCreatePostFormAccessNonAdmin(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('user@example.com');
        $client->loginUser($testUser);

        $client->request('GET', '/posts/new');

        $this->assertResponseStatusCodeSame(403);
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

    public function testCreateAndDeleteComment(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        $testAdmin = $userRepository->findOneByEmail('admin@example.com');
        $testUser = $userRepository->findOneByEmail('user@example.com');
        $client->loginUser($testUser);

        $post = new Post();
        $post->setTitle('Test Post');
        $post->setContent('This is a test post.');
        $post->setUser($testAdmin);
        $post->setImgUrl('/uploads/null.jpg');
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($post);
        $entityManager->flush();

        $postId = $post->getId();

        $crawler = $client->request('GET', "/posts/show/$postId");

        $this->assertResponseIsSuccessful();


        $form = $crawler->selectButton('post_submit_comment')->form([
            'comment[content]' => 'This is a test comment.',
        ]);

        $crawler = $client->submit($form);

        $this->assertResponseRedirects("/posts/show/$postId");

        $crawler = $client->followRedirect();

        $this->assertSelectorTextContains('.comment-content', 'This is a test comment.');
        $this->assertSelectorTextContains('.comment-author', $testUser->getUsername());

        $deleteCommentForm = $crawler->selectButton('comment_delete_btn')->form();
        $client->submit($deleteCommentForm);

        $this->assertResponseRedirects("/posts/show/$postId");
        $crawler = $client->followRedirect();

        $this->assertSelectorNotExists('.comment-content:contains("This is a test comment.")');
        $this->assertSelectorNotExists(sprintf('.comment-author:contains("%s")', $testUser->getUsername()));
    }
}
