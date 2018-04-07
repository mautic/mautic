<?php

namespace Mautic\UserBundle\Entity;

/**
 * Interface UserTokenRepositoryInterface.
 */
interface UserTokenRepositoryInterface
{
    /**
     * @param UserToken      $token
     * @param int            $signatureLength
     * @param \DateTime|null $expiration
     * @param bool           $oneTimeOnly
     *
     * @return mixed
     */
    public function sign(UserToken $token, $signatureLength = 32, \DateTime $expiration = null, $oneTimeOnly = true);

    /**
     * @param UserToken $token
     *
     * @return bool
     */
    public function verify(UserToken $token);
}
