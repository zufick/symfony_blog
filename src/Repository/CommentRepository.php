<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function getCommentsWithReplies(int $postId)
    {
        $comments = $this->createQueryBuilder('c')
            ->select('c', 'child', 'author')
            ->leftJoin('c.children', 'child')
            ->leftJoin('c.author', 'author')
            ->where('c.post = :postId')
            ->setParameter('postId', $postId)
            ->orderBy('c.created_at', 'ASC')
            ->getQuery()
            ->getResult();

        $groupedComments = [];
        foreach ($comments as $comment) {
            $groupedComments[$comment->getParent()?->getId()][] = $comment;
        }

        foreach ($comments as $comment) {
            $parentId = $comment->getParent()?->getId();
            if ($parentId) {
                $parentComment = $groupedComments[$parentId] ?? [];
                $parentComment[] = $comment;
                $groupedComments[$parentId] = $parentComment;
            }
        }

        return $groupedComments[null] ?? [];
    }

//    /**
//     * @return Comment[] Returns an array of Comment objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Comment
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
