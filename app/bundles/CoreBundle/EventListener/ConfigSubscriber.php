<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
/**
 * Class BuilderSubscriber
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
            ConfigEvents::CONFIG_ON_GENERATE   => array('onConfigGenerate', 0),
            ConfigEvents::CONFIG_PRE_SAVE       => array('onConfigBeforeSave', 0)
        );
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $paramsFile = $event->getContainer()->getParameter('kernel.root_dir') . '/bundles/CoreBundle/Config/parameters.php';

        if (file_exists($paramsFile)) {
            // Import the bundle configuration, $parameters is defined in this file
            include $paramsFile;
        } else {
            $parameters = array();
        }

        $event->addForm(array(
            'bundle' => 'CoreBundle',
            'formAlias' => 'coreconfig',
            'parameters' => $parameters
        ));
    }

    public function onConfigBeforeSave(ConfigEvent $event)
    {
        $values = $event->getConfig();
        $post   = $event->getPost();

        $passwords = array(
            'mailer_password' => $post->get('config[CoreBundle][mailer_password]', null, true),
            'transifex_password' => $post->get('config[CoreBundle][transifex_password]', null, true)
        );

        foreach ($passwords as $key => $password) {
            // Check to ensure we don't save a blank password to the config which may remove the user's old password
            if ($password == '') {
                unset($values['CoreBundle'][$key]);
            }
        }

        $event->setConfig($values);
    }
}
