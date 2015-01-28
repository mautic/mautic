<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticEmailMarketingBundle\Integration;

use Mautic\AddonBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Form;

/**
 * Class EmailAbstractIntegration
 *
 * @package MauticAddon\MauticCrmBundle\Integration
 */
abstract class EmailAbstractIntegration extends AbstractIntegration
{

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return array('push_lead');
    }

    /**
     * @param FormBuilder|Form $builder
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features' || $formArea == 'integration') {
            $name = strtolower($this->getName());
            if ($this->factory->serviceExists('mautic.form.type.emailmarketing.' . $name)) {
                if ($formArea == 'integration' && isset($data['leadFields']) && empty($data['list_settings']['leadFields'])) {
                    $data['list_settings']['leadFields'] = $data['leadFields'];
                }
                $builder->add('list_settings', 'emailmarketing_' . $name, array(
                    'label'     => false,
                    'form_area' => $formArea,
                    'data'      => (isset($data['list_settings'])) ? $data['list_settings'] : array()
                ));
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
     * Get the API helper
     *
     * @return Object
     */
    public function getApiHelper()
    {
        static $helper;
        if (empty($helper)) {
            $class = '\\MauticAddon\\MauticEmailMarketingBundle\\Api\\'.$this->getName().'Api';
            $helper = new $class($this);
        }

        return $helper;
    }
}