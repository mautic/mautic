<?php

/*
 * @copyright  2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticClearbitBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;

class ClearbitIntegration extends AbstractIntegration
{
    public function getName()
    {
        return 'Clearbit';
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * Return array of key => label elements that will be converted to inputs to
     * obtain from the user.
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        // Do not rename field. clearbit.js depends on it
        return [
            'apikey' => 'mautic.integration.clearbit.apikey',
        ];
    }

    /**
     * @param FormBuilder|Form $builder
     * @param array            $data
     * @param string           $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea === 'keys') {
            $builder->add(
                'auto_update',
                'yesno_button_group',
                [
                    'label' => 'mautic.plugin.clearbit.auto_update',
                    'data'  => (isset($data['auto_update'])) ? (bool) $data['auto_update'] : false,
                    'attr'  => [
                        'tooltip' => 'mautic.plugin.clearbit.auto_update.tooltip',
                    ],
                ]
            );
        }
    }

    /**
     * Allows integration to set a custom form template.
     *
     * @return string
     */
    public function getFormTemplate()
    {
        return 'MauticClearbitBundle:Integration:form.html.php';
    }

    public function shouldAutoUpdate()
    {
        $featureSettings = $this->getKeys();

        return (isset($featureSettings['auto_update'])) ? (bool) $featureSettings['auto_update'] : false;
    }
}
