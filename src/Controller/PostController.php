<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostController extends AbstractController
{
    #[Route('/', name: 'posts_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('post/index.html.twig', [
            'controller_name' => 'PostController',
        ]);
    }

    #[Route('/posts/new', name: 'post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager):Response
    {
        $post = new Post();

        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();

            $user = $this->getUser();
            if ($user) {
                $post->setUser($user);
            }


            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Уникальное имя файла
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                // Сохранение файла в директории
                $imageFile->move(
                    $this->getParameter('uploads_directory'), // Должна быть настроена директория
                    $newFilename
                );

                // Установка пути к файлу в сущность
                $post->setImgUrl(
                    $this->getParameter('uploads_base_url')
                    . '/' .
                    $newFilename
                );
            }


            $entityManager->persist($post);

            $entityManager->flush();

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }


        return $this->render('post/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/posts/show/{id}', name: 'post_show', methods: ['GET'])]
    public function show(Post $post): Response {
        return new Response($post->getId());
    }
}
