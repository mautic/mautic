<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use JMS\Serializer\Serializer;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\DelegatingEngine;

/**
 * Class CoreSubscriber
 *
 * @package Mautic\CoreBundle\EventListener
 */
class CommonSubscriber implements EventSubscriberInterface
{
    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine
     */
    protected $templating;

    /**
     * @var \JMS\SerializerBundle\JMSSerializerBundle
     */
    protected $serializer;

    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    protected $security;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var \Mautic\CoreBundle\Factory\MauticFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @param ContainerInterface $container
     */
    public function __construct (DelegatingEngine $templating,
                                 RequestStack $request_stack,
                                 Serializer $serializer,
                                 CorePermissions $security,
                                 TranslatorInterface $translator,
                                 EventDispatcherInterface $dispatcher,
                                 MauticFactory $factory,
                                 array $params
    )
    {
        $this->templating = $templating;
        $this->request    = $request_stack->getCurrentRequest();
        $this->security   = $security;
        $this->serializer = $serializer;
        $this->params     = $params;
        $this->dispatcher = $dispatcher;
        $this->factory    = $factory;
        $this->translator = $translator;
    }


    static public function getSubscribedEvents ()
    {
        return array();
    }
}