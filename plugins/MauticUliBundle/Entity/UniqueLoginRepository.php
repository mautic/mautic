<?php

namespace MauticPlugin\MauticUliBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class UniqueLoginRepository extends CommonRepository
{
    public function findByHash(string $hash): ?UniqueLogin
    {
        return $this->findOneBy(['hash' => $hash]);
    }

    public function findValidByHash(string $hash): ?UniqueLogin
    {
        $qb = $this->createQueryBuilder('uli');

        return $qb
            ->where('uli.hash = :hash')
            ->andWhere('uli.ttl >= :now')
            ->setParameter('hash', $hash)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteExpiredTokens(): int
    {
        $qb = $this->createQueryBuilder('uli');

        return $qb
            ->delete()
            ->where('uli.ttl < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function deleteByHash(string $hash): int
    {
        $qb = $this->createQueryBuilder('uli');

        return $qb
            ->delete()
            ->where('uli.hash = :hash')
            ->setParameter('hash', $hash)
            ->getQuery()
            ->execute();
    }
}