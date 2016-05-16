<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class IpLookupHelper
 */
class UserHelper
{
    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /***
     * UserHelper constructor.
     * @param SecurityContext $securityContext
     */
    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * @param bool $nullIfGuest
     * 
     * @return User|null
     */
    public function getUser($nullIfGuest = false)
    {
        $user  = null;
        $token = $this->securityContext->getToken();

        if ($token !== null) {
            $user = $token->getUser();
        }

        if (! $user instanceof User) {
            if ($nullIfGuest) {
                return null;
            }
            
            $user = new User();
            $user->isGuest = true;
        }

        return $user;
    }
}
