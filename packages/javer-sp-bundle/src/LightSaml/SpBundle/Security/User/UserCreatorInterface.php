<?php

/*
 * This file is part of the LightSAML SP-Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SpBundle\Security\User;

use LightSaml\Model\Protocol\Response;
use Symfony\Component\Security\Core\User\UserInterface;

interface UserCreatorInterface
{
    public function createUser(Response $response): ?UserInterface;
}
