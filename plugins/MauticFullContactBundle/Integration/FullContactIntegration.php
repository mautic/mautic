<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFullContactBundle\Integration;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FullContactIntegration extends AbstractIntegration
{
    public function getName()
    {
        return 'FullContact';
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
        // Do not rename field. fullcontact.js depends on it
        return [
            'apikey' => 'mautic.integration.fullcontact.apikey',
        ];
    }

    /**
     * @param FormBuilder|Form $builder
     * @param array            $data
     * @param string           $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('keys' === $formArea) {
            $builder->add(
                'test_api',
                ButtonType::class,
                [
                    'label' => 'mautic.plugin.fullcontact.test_api',
                    'attr'  => [
                        'class'   => 'btn btn-primary',
                        'style'   => 'margin-bottom: 10px',
                        'onclick' => 'Mautic.testFullContactApi(this)',
                    ],
                ]
            );

            $builder->add(
                'stats',
                TextareaType::class,
                [
                    'label_attr' => ['class' => 'control-label'],
                    'label'      => 'mautic.plugin.fullcontact.stats',
                    'required'   => false,
                    'attr'       => [
                        'class'    => 'form-control',
                        'rows'     => '6',
                        'readonly' => 'readonly',
                    ],
                ]
            );

            $builder->add(
                'auto_update',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.plugin.fullcontact.auto_update',
                    'data'  => (isset($data['auto_update'])) ? (bool) $data['auto_update'] : false,
                    'attr'  => [
                        'tooltip' => 'mautic.plugin.fullcontact.auto_update.tooltip',
                    ],
                ]
            );
        }
    }

    public function shouldAutoUpdate()
    {
        $featureSettings = $this->getKeys();

        return (isset($featureSettings['auto_update'])) ? (bool) $featureSettings['auto_update'] : false;
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string|array
     */
    public function getFormNotes($section)
    {
        if ('custom' === $section) {
            return [
                'template'   => 'MauticFullContactBundle:Integration:form.html.php',
                'parameters' => [
                    'mauticUrl' => $this->router->generate('mautic_plugin_fullcontact_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
            ];
        }

        return parent::getFormNotes($section);
    }
}
