<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEmailMarketingBundle\Integration;

/**
 * Class SparkpostIntegration
 */
class SparkpostIntegration extends EmailAbstractIntegration
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName ()
    {
        return 'Sparkpost';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'SparkPost';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType ()
    {
        return "key";
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            "api_key" => 'mautic.sparkpost.keyfield.api_key'
        );
    }

    /**
     * @param array $settings
     *
     * @return mixed
     */
    public function getAvailableLeadFields ($settings = array())
    {
        static $leadFields = array();

        if (empty($leadFields)) {
            if (isset($settings['list'])) {
                // Ajax update
                $listId = $settings['list'];
            } elseif (!empty($settings['feature_settings']['list_settings']['list'])) {
                // Form load
                $listId = $settings['feature_settings']['list_settings']['list'];
            } elseif (!empty($settings['list_settings']['list'])) {
                // Push action
                $listId = $settings['list_settings']['list'];
            }

            if (!empty($listId)) {
                $fields = $this->getApiHelper()->getCustomFields($listId);

                if (!empty($fields['data'][0]['merge_vars']) && count($fields['data'][0]['merge_vars'])) {
                    foreach ($fields['data'][0]['merge_vars'] as $field) {
                        $leadFields[$field['tag']] = array(
                            'label'    => $field['name'],
                            'type'     => 'string',
                            'required' => $field['req']
                        );
                    }
                }
            }
        }

        return $leadFields;
    }

    /**
     * @param       $lead
     * @param array $config
     *
     * @return bool
     */
    public function pushLead($lead, $config = array())
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        $mappedData = $this->populateLeadData($lead, $config);

        if (empty($mappedData)) {

            return false;
        } elseif (empty($mappedData['EMAIL'])) {

            return false;
        } elseif (!isset($config['list_settings'])) {

            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $email = $mappedData['EMAIL'];
                unset($mappedData['EMAIL']);

                $options = array();
                $options['double_optin'] = $config['list_settings']['doubleOptin'];
                $options['send_welcome'] = $config['list_settings']['sendWelcome'];
                $listId = $config['list_settings']['list'];

                $this->getApiHelper()->subscribeLead($email, $listId, $mappedData, $options);

                return true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return false;
    }
}
