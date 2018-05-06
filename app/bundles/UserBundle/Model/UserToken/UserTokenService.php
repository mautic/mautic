<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Model\UserToken;

use Mautic\CoreBundle\Helper\RandomHelper\RandomHelperInterface;
use Mautic\UserBundle\Entity\UserToken;
use Mautic\UserBundle\Entity\UserTokenRepositoryInterface;

/**
 * Class UserTokenService.
 */
final class UserTokenService implements UserTokenServiceInterface
{
    /**
     * @var RandomHelperInterface
     */
    private $randomHelper;

    /**
     * @var UserTokenRepositoryInterface
     */
    private $userTokenRepository;

    /**
     * UserTokenService constructor.
     *
     * @param RandomHelperInterface        $randomHelper
     * @param UserTokenRepositoryInterface $userTokenRepository
     */
    public function __construct(
        RandomHelperInterface $randomHelper,
        UserTokenRepositoryInterface $userTokenRepository
    ) {
        $this->randomHelper        = $randomHelper;
        $this->userTokenRepository = $userTokenRepository;
    }

    /**
     * @param UserToken $token
     * @param int       $secretLength
     *
     * @return UserToken
     */
    public function generateSecret(UserToken $token, $secretLength = 32)
    {
        do {
            $randomSecret   = $this->randomHelper->generate($secretLength);
            $isSecretUnique = $this->userTokenRepository->isSecretUnique($randomSecret);
        } while ($isSecretUnique === false);

        return $token->setSecret($randomSecret);
    }

    /**
     * @param UserToken $token
     *
     * @return bool
     */
    public function verify(UserToken $token)
    {
        return $this->userTokenRepository->verify($token);
    }
}
