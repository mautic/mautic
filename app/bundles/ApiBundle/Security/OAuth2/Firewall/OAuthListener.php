<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Security\OAuth2\Firewall;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class OAuthListener.
 */
class OAuthListener extends \FOS\OAuthServerBundle\Security\Firewall\OAuthListener
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        parent::handle($event);
    }
}
