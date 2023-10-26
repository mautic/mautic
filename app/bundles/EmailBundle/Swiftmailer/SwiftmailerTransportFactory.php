<?php

namespace Mautic\EmailBundle\Swiftmailer;

use Symfony\Bundle\SwiftmailerBundle\DependencyInjection\SwiftmailerTransportFactory as TransportFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RequestContext;

class SwiftmailerTransportFactory
{
    public static function createTransport(
        array $options,
        RequestContext $requestContext,
        \Swift_Events_EventDispatcher $eventDispatcher,
        ContainerInterface $container
    ) {
        // Try to get the transport from the container
        $options   = TransportFactory::resolveOptions($options);
        $transport = $options['transport'] ?? null;

        if ($transport && $container->has($transport)) {
            return $container->get($transport);
        }

        return TransportFactory::createTransport($options, $requestContext, $eventDispatcher);
    }
}
