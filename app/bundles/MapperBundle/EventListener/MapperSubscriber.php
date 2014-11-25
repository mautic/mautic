<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\EventListener;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\MapperBundle\Event\MapperSyncEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Mautic\MapperBundle\Event\MapperDashboardEvent;
use Mautic\MapperBundle\Event\MapperFormEvent;
use Mautic\MapperBundle\MapperEvents;
use Mautic\MapperBundle\Helper\IntegrationHelper;

/**
 * Class MapperSubscriber
 *
 * @package Mautic\MapperBundle\EventListener
 */
class MapperSubscriber implements EventSubscriberInterface
{
    protected $application;
    protected $request;
    protected $templating;
    protected $serializer;
    protected $security;
    protected $securityContext;
    protected $dispatcher;
    protected $factory;
    protected $params;
    protected $translator;


    public function __construct (MauticFactory $factory)
    {
        $this->factory         = $factory;
        $this->templating      = $factory->getTemplating();
        $this->request         = $factory->getRequest();
        $this->security        = $factory->getSecurity();
        $this->securityContext = $factory->getSecurityContext();
        $this->serializer      = $factory->getSerializer();
        $this->params          = $factory->getSystemParameters();
        $this->dispatcher      = $factory->getDispatcher();
        $this->translator      = $factory->getTranslator();
    }

    static public function getSubscribedEvents ()
    {
        return array(
            MapperEvents::FETCH_ICONS           => array('onFetchIcons', 0),
            MapperEvents::CLIENT_FORM_ON_BUILD  => array('onClientFormBuild', 0),
            MapperEvents::OBJECT_FORM_ON_BUILD  => array('onObjectFormBuild',0),
            MapperEvents::CALLBACK_API          => array('onCallbackApi', 0),
            MapperEvents::SYNC_DATA             => array('onSyncData', 0)
        );
    }

    /**
     * Find and add menu items
     *
     * @param IconEvent $event
     * @param           $name
     */
    public function onFetchIcons(MapperDashboardEvent $event)
    {

    }

    /**
     * Add Client form extra fields
     *
     * @param FormBuilderEvent $event
     */
    public function onClientFormBuild(MapperFormEvent $event)
    {

    }

    /**
     * Add Client form extra fields
     *
     * @param FormBuilderEvent $event
     */
    public function onObjectFormBuild(MapperFormEvent $event)
    {

    }

    /**
     *
     *
     * @param MapperSyncEvent $event
     */
    public function onSyncData(MapperSyncEvent $event)
    {
        if (empty($this->application)) {
            return;
        }

        $mapper = IntegrationHelper::getMapper($this->factory, $this->application, $event->getMapper());
        $mapper->create($this->factory,$event->getData());
    }
}