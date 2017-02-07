<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class QueueHelper.
 */
class QueueHelper
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

     /**
      * @return bool
      */
     public function trackMailUseQueue()
     {
         return $this->factory->getParameter('track_mail_use_queue');
     }

    public function getParameter($parameter)
    {
        return $this->factory->getParameter($parameter);
    }
}
