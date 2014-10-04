<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

//API Route Loader
$container->setDefinition('mautic.api_route_loader',
    new Definition(
        'Mautic\ApiBundle\Routing\RouteLoader',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('routing.loader');

//API docs loader
$container->setDefinition('mautic.api_docs_route_loader',
    new Definition(
        'Mautic\ApiBundle\Routing\ApiDocsLoader',
        array('%kernel.environment%')
    )
)
    ->addTag('routing.loader');

//oAuth1 service providers
$container->setDefinition('mautic.api.oauth1.nonce_provider',
    new Definition(
        'Mautic\ApiBundle\Provider\NonceProvider',
        array(new Reference('doctrine.orm.entity_manager'))
    )
);

//Hijack some OAuth1 classes to extend and fix bugs
$container->setParameter('bazinga.oauth.security.authentication.provider.class', 'Mautic\ApiBundle\Security\OAuth1\Authentication\Provider\OAuthProvider');
$container->setParameter('bazinga.oauth.security.authentication.listener.class', 'Mautic\ApiBundle\Security\OAuth1\Firewall\OAuthListener');

//Register custom authentication provider and listener that will be a door way to the API
$container->setDefinition('mautic_api.security.authentication.provider',
    new Definition(
        'Mautic\ApiBundle\Security\Authentication\Provider\ApiProvider', array('')
    )
);

$container->setDefinition('mautic_api.security.authentication.listener',
    new Definition(
        'Mautic\ApiBundle\Security\Firewall\ApiListener', array(
            new Reference('security.context'),
            new Reference('security.authentication.manager'),
            new Reference('mautic.factory'),
        )
    )
);