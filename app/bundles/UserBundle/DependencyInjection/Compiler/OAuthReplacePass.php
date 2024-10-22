<?php

declare(strict_types=1);

namespace Mautic\UserBundle\DependencyInjection\Compiler;

use Mautic\UserBundle\Security\Authenticator\Oauth2Authenticator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OAuthReplacePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('fos_oauth_server.security.authenticator.manager')) {
            return;
        }

        $oAuthAuthenticatorDefinition = $container->getDefinition('fos_oauth_server.security.authenticator.manager');
        $oAuthAuthenticatorDefinition->setClass(Oauth2Authenticator::class);
    }
}
