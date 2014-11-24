<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ZohoBundle\EventListener;

use Mautic\MapperBundle\Event\MapperAuthEvent;
use Mautic\MapperBundle\Event\MapperFormEvent;
use Mautic\MapperBundle\Event\MapperDashboardEvent;
use Mautic\MapperBundle\EventListener\MapperSubscriber;

class MapperListener extends MapperSubscriber
{
    /**
     * Add Sugar CRM to Mapper
     *
     * @param MapperDashboardEvent $event
     */
    public function onFetchIcons(MapperDashboardEvent $event)
    {
        $config = array(
            'name'        => 'Zoho CRM',
            'bundle' => 'zoho',
            'icon'        => 'app/bundles/ZohoBundle/Assets/images/zohocrm_128.png'
        );

        $event->addApplication($config);
    }

    /**
     * Add Sugar CRM fields to register client
     *
     * @param MapperFormEvent $event
     */
    public function onClientFormBuild(MapperFormEvent $event)
    {
        if ($event->getApplication() != 'zoho') {
            return;
        }

        $field = array(
            'child' => 'apikeys',
            'type' => 'zoho_apikeys',
            'params' => array(
                'label'       => 'mautic.zoho.form.api.keys',
                'required'    => false,
                'label_attr'  => array('class' => 'control-label')
            )
        );

        $event->addField($field);
    }

    public function onObjectFormBuild(MapperFormEvent $event)
    {
        if ($event->getApplication() != 'zoho') {
            return;
        }

        $field = array(
            'child' => 'mappedfields',
            'type' => 'zoho_mappedfields',
            'params' => array(
                'label'       => 'mautic.zoho.form.mapped.fields',
                'required'    => false,
                'label_attr'  => array('class' => 'control-label')
            )
        );

        $event->addField($field);
    }

    public function onCallbackApi(MapperAuthEvent $event)
    {
        if ($event->getApplication() != 'zoho') {
            return;
        }


    }
}