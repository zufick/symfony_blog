<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Service\ImageResizerService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// TODO: Caching
// TODO: Comments
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
        $postsPerPage = 8;

        $query = $repository->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->getQuery();

        $posts = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $postsPerPage
        );

        $latestPost = $repository->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'latestPost' => $latestPost
        ]);
    }

    #[Route('/posts/new', name: 'post_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ImageResizerService $imageResizerService,
    ):Response
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
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $targetDirectory = $this->getParameter('uploads_directory');
                $imageFilePath = $targetDirectory . '/' . $newFilename;

                $imageFile->move($targetDirectory, $newFilename);

                $imageResizerService->resizeImage($imageFilePath, 1280, 1280);

                $post->setImgUrl(
                    $this->getParameter('uploads_base_url') . '/' . $newFilename
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

    #[Route('/posts/edit/{id}', name: 'post_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Post $post,
        EntityManagerInterface $entityManager,
        ImageResizerService $imageResizerService
    ): Response {
        $this->denyAccessUnlessGranted('EDIT', $post);

        $form = $this->createForm(PostType::class, $post, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $targetDirectory = $this->getParameter('uploads_directory');
                $imageFilePath = $targetDirectory . '/' . $newFilename;

                $imageFile->move($targetDirectory, $newFilename);

                $imageResizerService->resizeImage($imageFilePath, 1280, 1280);

                $post->setImgUrl(
                    $this->getParameter('uploads_base_url') . '/' . $newFilename
                );
            }

            $entityManager->flush();

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    #[Route('/posts/delete/{id}', name: 'post_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Post $post,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('DELETE', $post);

        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('posts_index');
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
        $page = $request->query->getInt('page', 1);
        $postsPerPage = 8;

        $posts = $postRepository->findByCategorySlugWithPagination($slug, $page, $postsPerPage, $paginator);
        $category = $categoryRepository->findOneBy(['slug' => $slug]);



        return $this->render('category/show.html.twig', [
            'category' => $category,
            'posts' => $posts,
        ]);
    }

    #[Route('/user/{id}/posts', name: 'user_posts', methods: ['GET'])]
    public function userPosts(
        int $id,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $user = $entityManager->getRepository(User::class)->find($id);
        $postsPerPage = 8;

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $page = $request->query->getInt('page', 1);

        // Получение постов пользователя с пагинацией
        $posts = $postRepository->findByUserWithPagination($user, $page, $postsPerPage, $paginator);

        return $this->render('post/user_posts.html.twig', [
            'user' => $user,
            'posts' => $posts,
        ]);
    }

}
