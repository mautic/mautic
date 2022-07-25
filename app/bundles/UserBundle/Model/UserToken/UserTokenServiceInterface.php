<?php

namespace Mautic\UserBundle\Model\UserToken;

use Mautic\UserBundle\Entity\UserToken;

/**
 * Interface UserTokenServiceInterface.
 */
interface UserTokenServiceInterface
{
    /**
     * @param int $secretLength
     *
     * @return UserToken
     */
    public function generateSecret(UserToken $token, $secretLength = 32);

    /**
     * @return bool
     */
    public function verify(UserToken $token);
}
