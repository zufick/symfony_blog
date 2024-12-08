<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Service\ImageResizerService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Contracts\Cache\CacheInterface;

// TODO: Testing
class PostController extends AbstractController
{
    public function __construct(private CacheInterface $cache) {

    }


    #[Route('/', name: 'posts_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response
    {
        $repository = $entityManager->getRepository(Post::class);
        $postsPerPage = 8;
        $page = $request->query->getInt('page', 1);



        $posts = $this->cache->get('posts_page_' . $page, function () use ($repository, $paginator, $postsPerPage, $page) {
            $query = $repository->createQueryBuilder('p')
                ->leftJoin('p.category', 'c')
                ->addSelect('c') // Ensure that categories are included in the query result
                ->orderBy('p.id', 'DESC')
                ->getQuery();

            return $paginator->paginate(
                $query,
                $page,
                $postsPerPage
            );
        });

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

            $this->cache->clear('posts_page_');

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

            $this->cache->clear('posts_page_');

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
    public function show(Request $request, Post $post, CommentRepository $commentRepository): Response {
        $commentForm = $this->createForm(CommentType::class);

        $rootComments = $this->cache->get('post_comments_' . $post->getId(),function() use($post, $commentRepository) {
            return $commentRepository->getCommentsWithReplies($post->getId());
        });

        $response = $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $commentForm->createView(),
            'rootComments' => $rootComments,
        ]);

        return $response;
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

    // TODO: show nested comments when cached (not working currently)
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

        $posts = $postRepository->findByUserWithPagination($user, $page, $postsPerPage, $paginator);

        return $this->render('post/user_posts.html.twig', [
            'user' => $user,
            'posts' => $posts,
        ]);
    }

    #[Route('/posts/{id}/comment', name: 'post_comment', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addComment(
        Request $request,
        Post $post,
        EntityManagerInterface $entityManager
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setPost($post);
            $comment->setAuthor($this->getUser());

            $parentId = $request->request->get('parent_id');
            if ($parentId) {
                $parent = $entityManager->getRepository(Comment::class)->find($parentId);
                $comment->setParent($parent);
            }

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->cache->clear('post_comments_');
        }

        return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
    }

    #[Route('/comments/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    public function deleteComment(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $comment);

        $submittedToken = $request->getPayload()->get('_token');
        if (!$this->isCsrfTokenValid('del-comment'.$comment->getId(), $submittedToken)) {
            throw new InvalidCsrfTokenException();
        }

        $entityManager->remove($comment);
        $entityManager->flush();

        $this->cache->clear('post_comments_');

        return $this->redirectToRoute('post_show', ['id' => $comment->getPost()->getId()]);
    }
}
