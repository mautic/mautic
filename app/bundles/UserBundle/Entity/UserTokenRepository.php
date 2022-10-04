<?php

namespace Mautic\UserBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class UserTokenRepository.
 */
final class UserTokenRepository extends CommonRepository implements UserTokenRepositoryInterface
{
    /**
     * @param string $secret
     *
     * @return bool
     */
    public function isSecretUnique($secret)
    {
        $tokens = $this->createQueryBuilder('ut')
            ->where('ut.secret = :secret')
            ->setParameter('secret', $secret)
            ->setMaxResults(1)
            ->getQuery()->execute();

        return 0 === count($tokens);
    }

    /**
     * @return bool
     */
    public function verify(UserToken $token)
    {
        /** @var UserToken[] $userTokens */
        $userTokens = $this->createQueryBuilder('ut')
            ->where('ut.user = :user AND ut.authorizator = :authorizator AND ut.secret = :secret AND (ut.expiration IS NULL OR ut.expiration >= :now)')
            ->setParameter('user', $token->getUser())
            ->setParameter('authorizator', $token->getAuthorizator())
            ->setParameter('secret', $token->getSecret())
            ->setParameter('now', new \DateTime())
            ->setMaxResults(1)
            ->getQuery()->execute();
        $verified = (0 !== count($userTokens));
        if (false === $verified) {
            return false;
        }
        $userToken = reset($userTokens);
        if ($userToken->isOneTimeOnly()) {
            $this->deleteEntity($userToken);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteExpired($isDryRun = false)
    {
        $qb = $this->createQueryBuilder('ut');

        if ($isDryRun) {
            $qb->select('count(ut.id) as records');
        } else {
            $qb->delete(UserToken::class, 'ut');
        }

        return (int) $qb
            ->where('ut.expiration <= :current_datetime')
            ->setParameter(':current_datetime', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
