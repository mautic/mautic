<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Event\LeadSearchEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;

/**
 * Class SearchSubscriber.
 */
class StatSearchSubscriber extends CommonSubscriber
{
    /**
     * @var EmailModel
     */
    protected $emailModel;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * SearchSubscriber constructor.
     *
     * @param LeadModel  $leadModel
     * @param EmailModel $emailModel
     */
    public function __construct(LeadModel $leadModel, EmailModel $emailModel)
    {
        $this->leadModel  = $leadModel;
        $this->emailModel = $emailModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_ADVANCED_SEARCH => ['onStatSearch', 0],
        ];
    }

    /**
     * @param LeadSearchEvent $event
     */
    public function onStatSearch(LeadSearchEvent $event)
    {
        $params = $event->getParams();
        $event->setParams(['mama']);
    }
}
