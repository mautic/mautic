<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use \Symfony\Component\DependencyInjection\Definition;

$container->setDefinition('mautic.transport.amazon',
    new Definition (
        'Mautic\CoreBundle\Swiftmailer\Transport\AmazonTransport'
    )
);

$container->setDefinition('mautic.transport.mandrill',
    new Definition (
        'Mautic\CoreBundle\Swiftmailer\Transport\MandrillTransport'
    )
);

$container->setDefinition('mautic.transport.sendgrid',
    new Definition (
        'Mautic\CoreBundle\Swiftmailer\Transport\SendgridTransport'
    )
);