<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\OrderBy;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return Post[] Returns an array of Post objects filtered by tag, published and publication_date
     */
    public function findManyByTagName(string $tagName, bool $published, string $dateOrder)
    {
        $result = $this->createQueryBuilder('p')
            ->innerJoin('p.tag', 't')
            ->andWhere('t.name = :name')
            ->setParameter('name', $tagName)
            ->addOrderBy(new OrderBy('p.publication_date', $dateOrder));

        if ($published) {
            $result->andWhere('p.published = :published')
                ->setParameter('published', $published);
        }

        return $result
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Post[] Returns an array of Post objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Post
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
