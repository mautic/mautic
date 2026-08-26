<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Entity;

interface UserTokenRepositoryInterface
{
    /**
     * @param string $secret
     *
     * @return UserToken
     */
    public function isSecretUnique($secret);

    /**
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
