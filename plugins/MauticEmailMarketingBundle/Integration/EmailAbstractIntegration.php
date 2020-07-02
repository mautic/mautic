<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEmailMarketingBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;

/**
 * Class EmailAbstractIntegration.
 */
abstract class EmailAbstractIntegration extends AbstractIntegration
{
    protected $pushContactLink = false;

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * @param FormBuilder|Form $builder
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' == $formArea || 'integration' == $formArea) {
            if ($this->isAuthorized()) {
                $formType = $this->getFormType();

                if ($formType) {
                    if ('integration' == $formArea && isset($data['leadFields']) && empty($data['list_settings']['leadFields'])) {
                        $data['list_settings']['leadFields'] = $data['leadFields'];
                    }

                    $builder->add('list_settings', $formType, [
                        'label'     => false,
                        'form_area' => $formArea,
                        'data'      => (isset($data['list_settings'])) ? $data['list_settings'] : [],
                    ]);
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getFormTheme()
    {
        return 'MauticEmailMarketingBundle:FormTheme\EmailMarketing';
    }

    /**
     * Returns form type.
     *
     * @return string|null
     */
    abstract public function getFormType();

    /**
     * Get the API helper.
     *
     * @return object
     */
    public function getApiHelper()
    {
        static $helper;
        if (empty($helper)) {
            $class  = '\\MauticPlugin\\MauticEmailMarketingBundle\\Api\\'.$this->getName().'Api';
            $helper = new $class($this);
        }

        return $helper;
    }

    /**
     * Merges a config from integration_list with feature settings.
     *
     * @param array $config
     *
     * @return array|mixed
     */
    public function mergeConfigToFeatureSettings($config = [])
    {
        $featureSettings = $this->settings->getFeatureSettings();

        if (isset($config['config']['list_settings']['leadFields'])) {
            $config['config']['leadFields'] = $this->formatMatchedFields($config['config']['list_settings']['leadFields']);

            unset($config['config']['list_settings']['leadFields']);
        }

        if (empty($config['integration']) || (!empty($config['integration']) && $config['integration'] == $this->getName())) {
            $featureSettings = array_merge($featureSettings, $config['config']);
        }

        return $featureSettings;
    }
}
