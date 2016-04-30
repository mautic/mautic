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
}