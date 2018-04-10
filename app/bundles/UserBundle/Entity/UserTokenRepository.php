<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class UserTokenRepository.
 */
final class UserTokenRepository extends CommonRepository implements UserTokenRepositoryInterface
{
    /**
     * @param string $signature
     *
     * @return bool
     */
    public function isSignatureUnique($signature)
    {
        $tokens = $this->createQueryBuilder('ut')
            ->where('ut.signature = :signature')
            ->setParameter('signature', $signature)
            ->setMaxResults(1)
            ->getQuery()->execute();

        return count($tokens) === 0;
    }

    /**
     * @param UserToken $token
     *
     * @return bool
     */
    public function verify(UserToken $token)
    {
        /** @var UserToken[] $userTokens */
        $userTokens = $this->createQueryBuilder('ut')
            ->where('ut.user = :user AND ut.authorizator = :authorizator AND ut.signature = :signature AND (ut.expiration IS NULL OR ut.expiration >= :now)')
            ->setParameter('user', $token->getUser())
            ->setParameter('authorizator', $token->getAuthorizator())
            ->setParameter('signature', $token->getSignature())
            ->setParameter('now', new \DateTime())
            ->setMaxResults(1)
            ->getQuery()->execute();
        $verified = (count($userTokens) !== 0);
        if ($verified === false) {
            return false;
        }
        $userToken = reset($userTokens);
        if ($userToken->isOneTimeOnly()) {
            $this->deleteEntity($userToken);
        }

        return true;
    }
}
