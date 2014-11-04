<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\EventListener;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Mautic\MapperBundle\Event as MapperEvents;

/**
 * Class MapperSubscriber
 *
 * @package Mautic\MapperBundle\EventListener
 */
class MapperSubscriber implements EventSubscriberInterface
{
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
        return array();
    }

    /**
     * Find and add menu items
     *
     * @param IconEvent $event
     * @param           $name
     */
    protected function buildIcons(MapperEvents\MapperDashboardEvent $event)
    {
        $security = $event->getSecurity();
        $request  = $this->factory->getRequest();
        $bundles = $this->factory->getParameter('bundles');
        $icons = array();

        foreach ($bundles as $bundle) {
            //check common place
            $path = $bundle['directory'] . "/Config/mapper/client.php";
            if (!file_exists($path)) {
                //else check for just a mapper.php file
                $path = $bundle['directory'] . "/Config/mapper.php";
                $recheck = true;
            } else {
                $recheck = false;
            }

            if (!$recheck || file_exists($path)) {
                $config = include $path;
                if (!isset($config['application'])) {
                    continue;
                }
                $event->addApplication(basename($bundle['directory']), $config['application']);
            }
        }
    }
}