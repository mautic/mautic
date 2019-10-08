<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Authentication\Token;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Class PluginToken.
 */
class PluginToken extends AbstractToken
{
    /**
     * @var
     */
    protected $providerKey;

    /**
     * @var string
     */
    protected $credentials;

    /**
     * @var
     */
    protected $authenticatingService;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @param array|\Symfony\Component\Security\Core\Role\RoleInterface[] $providerKey
     * @param null                                                        $authenticatingService
     * @param string                                                      $user
     * @param string                                                      $credentials
     * @param array                                                       $roles
     * @param Response                                                    $response
     */
    public function __construct(
        $providerKey,
        $authenticatingService = null,
        $user = '',
        $credentials = '',
        array $roles = [],
        Response $response = null
    ) {
        parent::__construct($roles);

        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->setUser($user);
        $this->authenticatingService = $authenticatingService;
        $this->credentials           = $credentials;
        $this->providerKey           = $providerKey;
        $this->response              = $response;

        parent::setAuthenticated(count($roles) > 0);
    }

    /**
     * @return string
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * @return array|\Symfony\Component\Security\Core\Role\RoleInterface[]
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }

    public function getAuthenticatingService()
    {
        return $this->authenticatingService;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->authenticatingService, $this->credentials, $this->providerKey, parent::serialize()]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->authenticatingService, $this->credentials, $this->providerKey, $parentStr) = unserialize($serialized);
        parent::unserialize($parentStr);
    }
}
