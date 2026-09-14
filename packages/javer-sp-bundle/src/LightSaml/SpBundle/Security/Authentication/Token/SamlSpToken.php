<?php

/*
 * This file is part of the LightSAML SP-Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SpBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class SamlSpToken extends PostAuthenticationToken
{
    /**
     * @param UserInterface $user
     * @param string        $firewallName
     * @param string[]      $roles
     * @param mixed[]       $attributes
     */
    public function __construct(
        UserInterface $user,
        string $firewallName,
        array $roles,
        array $attributes,
    )
    {
        parent::__construct($user, $firewallName, $roles);

        $this->setAttributes($attributes);
    }
}
