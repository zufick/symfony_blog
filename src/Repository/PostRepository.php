<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findByCategorySlugWithPagination(
        string $categorySlug,
        int $page,
        int $limit,
        PaginatorInterface $paginator
    ): PaginationInterface
    {
        $query = $this->createQueryBuilder('p')
            ->join('p.category', 'c') // Связываем Post с Category
            ->where('c.slug = :slug') // Указываем условие по slug категории
            ->setParameter('slug', $categorySlug)
            ->orderBy('p.id', 'DESC') // Упорядочиваем по дате создания
            ->getQuery();

        return $paginator->paginate(
            $query,  // Query для выполнения
            $page,   // Номер текущей страницы
            $limit   // Количество записей на страницу
        );
    }

    public function findByUserWithPagination(
        User $user,
        int $page,
        int $limit,
        PaginatorInterface $paginator
    ): PaginationInterface
    {
        $query = $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.id', 'DESC') // Сортировка по ID (или измените на нужный порядок)
            ->getQuery();

        return $paginator->paginate(
            $query,  // Query для выполнения
            $page,   // Номер текущей страницы
            $limit   // Количество записей на страницу
        );
    }


//    /**
//     * @return Post[] Returns an array of Post objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Post
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
