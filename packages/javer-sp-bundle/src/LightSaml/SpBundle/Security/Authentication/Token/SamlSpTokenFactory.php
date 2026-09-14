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

use LightSaml\Model\Protocol\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class SamlSpTokenFactory implements SamlSpTokenFactoryInterface
{
    /**
     * @param UserInterface $user
     * @param string        $firewallName
     * @param mixed[]       $attributes
     * @param Response      $response
     *
     * @return SamlSpToken
     */
    public function create(
        UserInterface $user,
        string $firewallName,
        array $attributes,
        Response $response,
    ): SamlSpToken
    {
        return new SamlSpToken($user, $firewallName, $user->getRoles(), $attributes);
    }
}
