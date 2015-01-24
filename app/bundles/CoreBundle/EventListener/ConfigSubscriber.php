<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
/**
 * Class ConfigSubscriber
 *
 * @package Mautic\CoreBundle\EventListener
 */
class ConfigSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            ConfigEvents::CONFIG_ON_GENERATE    => array('onConfigGenerate', 0),
            ConfigEvents::CONFIG_PRE_SAVE       => array('onConfigBeforeSave', 0)
        );
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addForm(array(
            'bundle'        => 'CoreBundle',
            'formAlias'     => 'coreconfig',
            'formTheme'     => 'MauticCoreBundle:FormTheme\Config',
            'parameters'    => $event->getParametersFromConfig('MauticCoreBundle')
        ));
    }

    public function onConfigBeforeSave(ConfigEvent $event)
    {
        $values = $event->getConfig();

        $passwords = array(
            'mailer_password'       => $values['coreconfig']['mailer_password'],
            'transifex_password'    => $values['coreconfig']['transifex_password']
        );

        foreach ($passwords as $key => $password) {
            // Check to ensure we don't save a blank password to the config which may remove the user's old password
            if ($password == '') {
                unset($values['coreconfig'][$key]);
            }
        }

        $event->setConfig($values);
    }
}
