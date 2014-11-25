<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SugarcrmBundle\EventListener;

use Mautic\MapperBundle\Event\MapperAuthEvent;
use Mautic\MapperBundle\Event\MapperFormEvent;
use Mautic\MapperBundle\Event\MapperDashboardEvent;
use Mautic\MapperBundle\EventListener\MapperSubscriber;
use Mautic\MapperBundle\Helper\IntegrationHelper;

class MapperListener extends MapperSubscriber
{
    /**
     * @var string
     */
    protected $application = 'sugarcrm';

    /**
     * Add Sugar CRM to Mapper
     *
     * @param MapperDashboardEvent $event
     */
    public function onFetchIcons(MapperDashboardEvent $event)
    {
        $config = array(
            'name'        => 'Sugar CRM',
            'bundle' => 'sugarcrm',
            'icon'        => 'app/bundles/SugarcrmBundle/Assets/images/sugarcrm_128.png'
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
        if ($event->getApplication() != $this->application) {
            return;
        }

        $field = array(
            'child' => 'apikeys',
            'type' => 'sugarcrm_apikeys',
            'params' => array(
                'label'       => 'mautic.sugarcrm.form.api.keys',
                'required'    => false,
                'label_attr'  => array('class' => 'control-label')
            )
        );

        $event->addField($field);
    }

    public function onObjectFormBuild(MapperFormEvent $event)
    {
        if ($event->getApplication() != $this->application) {
            return;
        }

        $field = array(
            'child' => 'mappedfields',
            'type' => 'sugarcrm_mappedfields',
            'params' => array(
                'label'       => 'mautic.sugarcrm.form.mapped.fields',
                'required'    => false,
                'label_attr'  => array('class' => 'control-label')
            )
        );

        $event->addField($field);
    }

    public function onCallbackApi(MapperAuthEvent $event)
    {
        if ($event->getApplication() != $this->application) {
            return;
        }

        $postActionRedirect = array();



        
        $event->setPostActionRedirect($postActionRedirect);
    }
}