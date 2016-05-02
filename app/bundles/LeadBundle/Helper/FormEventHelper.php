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

        if ($factory->getRequest()->server->get('QUERY_STRING')) {
            parse_str($factory->getRequest()->server->get('QUERY_STRING'), $query);
            $referrerURL = $factory->getRequest()->server->get('HTTP_REFERER');
            $pageURI = $factory->getRequest()->server->get('REQUEST_URI');
            $referrerParsedUrl = parse_url($referrerURL);
            parse_str($referrerParsedUrl['query'],$queryReferrer);

            if(key_exists('utm_campaign',$queryReferrer)){
                $utmValues['utm_campaign'] =  $queryReferrer['utm_campaign'];
            }

            if(key_exists('utm_content', $queryReferrer)){
                $utmValues['utm_content'] =  $queryReferrer['utm_content'];
            }

            if(key_exists('utm_medium', $queryReferrer)){
                $utmValues['utm_medium'] =  $queryReferrer['utm_medium'];
            }

            if(key_exists('utm_source', $queryReferrer)){
                $utmValues['utm_source'] =  $queryReferrer['utm_source'];
            }

            if(key_exists('utm_term', $queryReferrer)){
                $utmValues['utm_term'] =  $queryReferrer['utm_term'];
            }
            $utmValues['query'] = $queryReferrer;
            $utmValues['referrer'] = $referrerURL;
            $utmValues['remote_host'] = $referrerParsedUrl['host'];
            $utmValues['url'] = $pageURI;
            $utmValues['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            
            $factory->getLogger()->addError(print_r($utmValues,true));
        }

       // $leadModel->modifyUtmTags($lead, $config['add_utmtags']);
    }
}
