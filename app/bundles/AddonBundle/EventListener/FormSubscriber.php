<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;

/**
 * Class FormSubscriber
 */
class FormSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            FormEvents::FORM_ON_BUILD => array('onFormBuild', 0)
        );
    }

    /**
     * @param FormBuilderEvent $event
     */
    public function onFormBuild (FormBuilderEvent $event)
    {
        $action = array(
            'group'       => 'mautic.addon.form.actions',
            'label'       => 'mautic.addon.actions.push_lead',
            'formType'    => 'integration_list',
            'formTheme'   => 'MauticAddonBundle:FormTheme\Integration',
            'callback'    => array('\\Mautic\\AddonBundle\\Helper\\EventHelper', 'pushLead')
        );

        $event->addSubmitAction('addon.leadpush', $action);
    }
}