<?php

declare(strict_types=1);

namespace Mautic\UserBundle\DependencyInjection\Compiler;

use Mautic\UserBundle\Security\Authenticator\Oauth2Authenticator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class OAuthReplacePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('fos_oauth_server.security.authenticator.manager')) {
            return;
        }

        $oAuthAuthenticatorDefinition = $container->getDefinition('fos_oauth_server.security.authenticator.manager');
        $oAuthAuthenticatorDefinition
            ->setClass(Oauth2Authenticator::class)
            ->setArgument(2, new Reference('security.token.permissions'));

        foreach ([
            'security.authenticator.oauth2',
            'security.authenticator.oauth2.api',
            'security.authenticator.oauth2.v2api',
        ] as $serviceId) {
            if (!$container->hasDefinition($serviceId)) {
                continue;
            }

            $container->getDefinition($serviceId)
                ->setClass(Oauth2Authenticator::class)
                ->setArgument(2, new Reference('security.token.permissions'));
        }
    }
}
