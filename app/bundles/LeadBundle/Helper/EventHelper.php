<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
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

        $addTags    = (!empty($config['add_tags'])) ? $config['add_tags'] : [];
        $removeTags = (!empty($config['remove_tags'])) ? $config['remove_tags'] : [];

        $leadModel->modifyTags($lead, $addTags, $removeTags);

        return true;
    }

    /**
     * @param $action
     * @param $factory
     */
    public static function addUtmTags($action, $factory, $config)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $factory->getModel('lead');
        $lead      = $leadModel->getCurrentLead();

        $queryReferer = [];

        parse_str($factory->getRequest()->server->get('QUERY_STRING'), $query);
        $refererURL       = $factory->getRequest()->server->get('HTTP_REFERER');
        $pageURI          = $factory->getRequest()->server->get('REQUEST_URI');
        $refererParsedUrl = parse_url($refererURL);

        if (isset($refererParsedUrl['query'])) {
            parse_str($refererParsedUrl['query'], $queryReferer);
        }

        $utmValues = new UtmTag();
        $utmValues->setDateAdded(new \Datetime());

        if (key_exists('utm_campaign', $query)) {
            $utmValues->setUtmCampaign($query['utm_campaign']);
        } elseif (key_exists('utm_campaign', $queryReferer)) {
            $utmValues->setUtmCampaign($queryReferer['utm_campaign']);
        }

        if (key_exists('utm_content', $query)) {
            $utmValues->setUtmCampaign($query['utm_content']);
        } elseif (key_exists('utm_content', $queryReferer)) {
            $utmValues->setUtmContent($queryReferer['utm_content']);
        }

        if (key_exists('utm_medium', $query)) {
            $utmValues->setUtmCampaign($query['utm_medium']);
        } elseif (key_exists('utm_medium', $queryReferer)) {
            $utmValues->setUtmMedium($queryReferer['utm_medium']);
        }

        if (key_exists('utm_source', $query)) {
            $utmValues->setUtmCampaign($query['utm_source']);
        } elseif (key_exists('utm_source', $queryReferer)) {
            $utmValues->setUtmSource($queryReferer['utm_source']);
        }

        if (key_exists('utm_term', $query)) {
            $utmValues->setUtmCampaign($query['utm_term']);
        } elseif (key_exists('utm_term', $queryReferer)) {
            $utmValues->setUtmTerm($queryReferer['utm_term']);
        }

        $query = $factory->getRequest()->query->all();

        $utmValues->setLead($lead);
        $utmValues->setQuery($query);
        $utmValues->setReferer($refererURL);
        $utmValues->setRemoteHost($refererParsedUrl['host']);
        $utmValues->setUrl($pageURI);
        $utmValues->setUserAgent($_SERVER['HTTP_USER_AGENT']);
        $em   = $factory->getEntityManager();
        $repo = $em->getRepository('MauticLeadBundle:UtmTag');
        $repo->saveEntity($utmValues);
        $leadModel->setUtmTags($lead, $utmValues);
    }
}
