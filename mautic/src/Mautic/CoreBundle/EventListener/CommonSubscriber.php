<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CoreSubscriber
 *
 * @package Mautic\CoreBundle\EventListener
 */
class CommonSubscriber implements EventSubscriberInterface
{
    protected $request;
    protected $templating;
    protected $serializer;
    protected $security;
    protected $dispatcher;
    protected $factory;
    protected $params;
    protected $translator;


    public function __construct (MauticFactory $factory)
    {
        $this->templating = $factory->getTemplating();
        $this->request    = $factory->getRequest();
        $this->security   = $factory->getSecurity();
        $this->serializer = $factory->getSerializer();
        $this->params     = $factory->getSystemParameters();
        $this->dispatcher = $factory->getDispatcher();
        $this->factory    =& $factory;
        $this->translator = $factory->getTemplating();
    }

    static public function getSubscribedEvents ()
    {
        return array();
    }
}