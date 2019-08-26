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

/**
 * Interface UserTokenRepositoryInterface.
 */
interface UserTokenRepositoryInterface
{
    /**
     * @param string $secret
     *
     * @return UserToken
     */
    public function isSecretUnique($secret);

    /**
     * @param UserToken $token
     *
     * @return bool
     */
    public function verify(UserToken $token);

    /**
     * Delete expired user tokens.
     *
     * @param bool $isDryRun
     *
     * @return int Number of selected or deleted rows
     */
    public function deleteExpired($isDryRun = false);
}
