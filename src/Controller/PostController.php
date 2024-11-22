<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Post;
use App\Form\PostType;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostController extends AbstractController
{
    #[Route('/', name: 'posts_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response
    {
        $repository = $entityManager->getRepository(Post::class);

        $query = $repository->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC') // Упорядочиваем по дате создания
            ->getQuery();

        $posts = $paginator->paginate(
            $query, // QueryBuilder или Query
            $request->query->getInt('page', 1), // Текущая страница
            8 // Количество постов на странице
        );

        $latestPost = $repository->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(1) // Ограничиваем результат одним постом
            ->getQuery()
            ->getOneOrNullResult(); // Получаем объект поста или null

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'latestPost' => $latestPost
        ]);
    }

    #[Route('/posts/new', name: 'post_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager):Response
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
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/category/{slug}', name: 'category_show', methods: ['GET'])]
    public function showCategory(
        string $slug,
        PostRepository $postRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        Request $request,
    ): Response {
        $page = $request->query->getInt('page', 1); // Получение текущей страницы из параметра URL

        $posts = $postRepository->findByCategorySlugWithPagination($slug, $page, 8, $paginator);
        $category = $categoryRepository->findOneBy(['slug' => $slug]);



        return $this->render('category/show.html.twig', [
            'category' => $category,
            'posts' => $posts,
        ]);
    }
}
