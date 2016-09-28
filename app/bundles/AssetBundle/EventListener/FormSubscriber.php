<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;

/**
 * Class FormSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class FormSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            FormEvents::FORM_ON_BUILD => array('onFormBuilder', 0),
        );
    }

    /**
     * Add a lead generation action to available form submit actions
     *
     * @param FormBuilderEvent $event
     */
    public function onFormBuilder(FormBuilderEvent $event)
    {
        //add form submit actions
        $action = array(
            'group'              => 'mautic.asset.actions',
            'label'              => 'mautic.asset.asset.submitaction.downloadfile',
            'description'        => 'mautic.asset.asset.submitaction.downloadfile_descr',
            'formType'           => 'asset_submitaction_downloadfile',
            'formTypeCleanMasks' => array('message' => 'html'),
            'callback'           => '\Mautic\AssetBundle\Helper\FormSubmitHelper::onFormSubmit'
        );

        $event->addSubmitAction('asset.download', $action);
    }
}