<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\LeadBundle\Entity\PointsChangeLog;

/**
 * Class FormEventHelper
 *
 * @package Mautic\LeadBundle\Helper
 */
class FormEventHelper
{
    /**
     * @param               $lead
     * @param MauticFactory $factory
     * @param               $action
     * @param               $config
     * @param               $form
     */
    public static function changePoints ($lead, MauticFactory $factory, $action, $config, $form)
    {
        $model = $factory->getModel('lead');

        //create a new points change event
        $event = new PointsChangeLog();
        $event->setType('form');
        $event->setEventName($form->getId() . ":" . $form->getName());
        $event->setActionName($action->getName());
        $event->setIpAddress($factory->getIpAddress());
        $event->setDateAdded(new \DateTime());

        $event->setDelta($config['points']);
        $event->setLead($lead);

        $lead->addPointsChangeLog($event);
        $lead->addToPoints($config['points']);

        $model->saveEntity($lead, false);
    }

    /**
     * @param $action
     * @param $factory
     */
    public static function changeLists ($action, $factory)
    {
        $properties = $action->getProperties();

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel  = $factory->getModel('lead');
        $lead       = $leadModel->getCurrentLead();
        $addTo      = $properties['addToLists'];
        $removeFrom = $properties['removeFromLists'];

        if (!empty($addTo)) {
            $leadModel->addToLists($lead, $addTo);
        }

        if (!empty($removeFrom)) {
            $leadModel->removeFromLists($lead, $removeFrom);
        }
    }

    /**
     * @param $action
     * @param $factory
     */
    public static function addUtmTags ($action, $factory,$config)
    {
        $properties = $action->getProperties();

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel  = $factory->getModel('lead');
        $lead       = $leadModel->getCurrentLead();
        $factory->getLogger()->addError(print_r($config,true));
        foreach($config['add_utmtags'] as $utmTag){
            if ($factory->getRequest()->server->get('QUERY_STRING')) {
                parse_str($factory->getRequest()->server->get('QUERY_STRING'), $query);
                $pageURL = $factory->getRequest()->server->get('HTTP_REFERER');
                $pageURI = $factory->getRequest()->server->get('REQUEST_URI');
                parse_str($pageURL,$queryReferrer);
                $factory->getLogger()->addError(print_r($queryReferrer,true));
                $factory->getLogger()->addError(print_r($pageURI,true));
            }
        }
        $leadModel->modifyUtmTags($lead, $config['add_utmtags']);
    }
}
