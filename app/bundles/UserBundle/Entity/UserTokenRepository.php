<?php

namespace Mautic\UserBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class UserTokenRepository.
 */
final class UserTokenRepository extends CommonRepository implements UserTokenRepositoryInterface
{
    /**
     * @param UserToken      $token
     * @param int            $signatureLength
     * @param \DateTime|null $expiration
     * @param bool           $oneTimeOnly
     *
     * @return UserToken
     */
    public function sign(UserToken $token, $signatureLength = 32, \DateTime $expiration = null, $oneTimeOnly = true)
    {
        $signature = $this->_em->getConnection()
            ->fetchColumn('SELECT signUserToken(:userId, :authorizator, :signatureLength, :expiration, :oneTimeOnly)', [
                'userId'          => $token->getUser()->getId(),
                'authorizator'    => $token->getAuthorizator(),
                'signatureLength' => $signatureLength,
                'expiration'      => $expiration->format('Y-m-d H:i:s'),
                'oneTimeOnly'     => $oneTimeOnly,
            ]);

        return $token->sign($signature);
    }

    /**
     * @param UserToken $token
     *
     * @return bool
     */
    public function verify(UserToken $token)
    {
        $verification = $this->_em->getConnection()
            ->fetchColumn('SELECT verifyUserToken(:userId, :authorizator, :signature)', [
                'userId'       => $token->getUser()->getId(),
                'authorizator' => $token->getAuthorizator(),
                'signature'    => $token->getSignature(),
            ]);

        return (bool) $verification;
    }
}
