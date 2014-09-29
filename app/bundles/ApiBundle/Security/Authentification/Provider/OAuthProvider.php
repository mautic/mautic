<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Security\Authentification\Provider;

use Bazinga\OAuthServerBundle\Security\Authentification\Token\OAuthToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OAuthProvider extends \Bazinga\OAuthServerBundle\Security\Authentification\Provider\OAuthProvider
{

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return ($token instanceof OAuthToken);
    }
}
