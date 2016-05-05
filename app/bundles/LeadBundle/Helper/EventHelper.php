<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\UtmTag;


class EventHelper
{
    /**
     * @param Lead          $lead
     * @param               $config
     * @param MauticFactory $factory
     *
     * @return bool
     */
    public static function updateTags(Lead $lead, $config, MauticFactory $factory)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $factory->getModel('lead');

        $addTags    = (!empty($config['add_tags'])) ? $config['add_tags'] : array();
        $removeTags = (!empty($config['remove_tags'])) ? $config['remove_tags'] : array();

        $leadModel->modifyTags($lead, $addTags, $removeTags);

        return true;
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
        $queryReferrer = array();

        if ($factory->getRequest()->server->get('QUERY_STRING')) {
            parse_str($factory->getRequest()->server->get('QUERY_STRING'), $query);
            $referrerURL = $factory->getRequest()->server->get('HTTP_REFERER');
            $pageURI = $factory->getRequest()->server->get('REQUEST_URI');
            $referrerParsedUrl = parse_url($referrerURL);

            if(isset($referrerParsedUrl['query'])){
                parse_str($referrerParsedUrl['query'],$queryReferrer);
            }

            $utmValues = new UtmTag();

            if(key_exists('utm_campaign',$queryReferrer)){
                $utmValues->setUtmCampaign($queryReferrer['utm_campaign']);
            }

            if(key_exists('utm_content', $queryReferrer)){
                $utmValues->setUtmConent($queryReferrer['utm_content']);
            }

            if(key_exists('utm_medium', $queryReferrer)){
                $utmValues['utm_medium'] =  $queryReferrer['utm_medium'];
            }

            if(key_exists('utm_source', $queryReferrer)){
                $utmValues->setUtmSource($queryReferrer['utm_source']);
            }

            if(key_exists('utm_term', $queryReferrer)){
                $utmValues->setUtmTerm($queryReferrer['utm_term']);
            }

            $query = $factory->getRequest()->query->all();

            $utmValues->setLead($lead);
            $utmValues->setQuery($query);
            $utmValues->setReferrer($referrerURL);
            $utmValues->setRemoteHost($referrerParsedUrl['host']);
            $utmValues->setUrl($pageURI);
            $utmValues->setUserAgent($_SERVER['HTTP_USER_AGENT']);
            $em   = $factory->getEntityManager();
            $repo = $em->getRepository('MauticLeadBundle:UtmTag');
            $repo->saveEntity($utmValues);

        }

        $leadModel->setUtmTags($lead, $utmValues);
    }

}