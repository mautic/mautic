<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Authentication\Token;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Class PluginToken
 */
class PluginToken extends AbstractToken
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var
     */
    protected $providerKey;

    /**
     * @param array|\Symfony\Component\Security\Core\Role\RoleInterface[] $providerKey
     * @param array                                                       $roles
     */
    public function __construct($providerKey, array $roles = array())
    {
        parent::__construct($roles);

        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->providerKey = $providerKey;

        parent::setAuthenticated(count($roles) > 0);
    }
    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getCredentials()
    {
        return '';
    }

    /**
     * @return array|\Symfony\Component\Security\Core\Role\RoleInterface[]
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }
}