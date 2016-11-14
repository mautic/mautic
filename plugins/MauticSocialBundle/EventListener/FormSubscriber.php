<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;

/**
 * Class FormSubscriber.
 */
class FormSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD => ['onFormBuild', 0],
        ];
    }

    /**
     * @param FormBuilderEvent $event
     */
    public function onFormBuild(FormBuilderEvent $event)
    {
        $action = [
            'label'          => 'mautic.plugin.actions.socialLogin',
            'formType'       => 'sociallogin',
            'template'       => 'MauticSocialBundle:Integration:login.html.php',
            'builderOptions' => [
                'addLeadFieldList' => false,
                'addIsRequired'    => false,
                'addDefaultValue'  => false,
                'addSaveResult'    => false,
            ],
        ];

        $event->addFormField('plugin.loginSocial', $action);
    }
}
