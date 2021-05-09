<?php

namespace App\Repository;

use App\Entity\Block;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Block|null find($id, $lockMode = null, $lockVersion = null)
 * @method Block|null findOneBy(array $criteria, array $orderBy = null)
 * @method Block[]    findAll()
 * @method Block[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Block::class);
    }

    public function getSortedBlocks($page)
    {
        return $this->createQueryBuilder('block')
            ->andWhere('block.page = :val')
            ->setParameter('val', $page)
            ->orderBy('block.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLast($page)
    {
        return $this->createQueryBuilder('block')
            ->andWhere('block.page = :val')
            ->setParameter('val', $page)
            ->orderBy('block.position', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

    public function getAllAfter($block)
    {
        return $this->createQueryBuilder('block')
            ->andWhere('block.position > :val')
            ->setParameter('val', $block->getPosition())
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Block[] Returns an array of Block objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Block
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
