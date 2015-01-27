<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Integration;


use Mautic\AddonBundle\Entity\Integration;
use Mautic\AddonBundle\Integration\AbstractIntegration;
use MauticAddon\MauticCrmBundle\Api\CrmApi;

/**
 * Class CrmAbstractIntegration
 *
 * @package MauticAddon\MauticCrmBundle\Integration
 */
abstract class CrmAbstractIntegration extends AbstractIntegration
{

    protected $auth;

    /**
     * @param Integration $settings
     */
    public function setIntegrationSettings(Integration $settings)
    {
        //make sure URL does not have ending /
        $keys = $this->getDecryptedApiKeys($settings);
        if (isset($keys['url']) && substr($keys['url'], -1) == '/') {
            $keys['url'] = substr($keys['url'], 0, -1);
            $this->encryptAndSetApiKeys($keys, $settings);
        }

        parent::setIntegrationSettings($settings);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'rest';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return array('push_lead');
    }

    /**
     * Return key recognized by CRM
     *
     * @param $key
     * @param $field
     *
     * @return mixed
     */
    public function convertLeadFieldKey($key, $field)
    {
        return $key;
    }

    /**
     * Match lead data with CRM fields
     *
     * @param $lead
     *
     * @return array
     */
    public function populateLeadData($lead)
    {
        $featureSettings = $this->settings->getFeatureSettings();

        if (empty($featureSettings['leadFields'])) {
            return false;
        }

        $fields          = $lead->getFields(true);
        $leadFields      = $featureSettings['leadFields'];
        $availableFields = $this->getAvailableFields();

        $unknown = $this->factory->getTranslator()->trans('mautic.crm.form.lead.unknown');
        $matched = array();
        foreach ($availableFields as $key => $field) {
            $crmKey = $this->convertLeadFieldKey($key, $field);

            if (isset($leadFields[$key])) {
                $mauticKey = $leadFields[$key];
                if (isset($fields[$mauticKey]) && !empty($fields[$mauticKey]['value'])) {
                    $matched[$crmKey] = $fields[$mauticKey]['value'];
                }
            }

            if (!empty($field['required']) && empty($matched[$crmKey])) {
                $matched[$crmKey] = $unknown;
            }
        }

        return $matched;
    }

    /**
     * @param $lead
     */
    public function pushLead($lead)
    {
        $mappedData = $this->populateLeadData($lead);

        $this->amendLeadDataBeforePush($mappedData);

        if (empty($mappedData)) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                CrmApi::getContext($this, "lead")->create($mappedData);
                return true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }
        return false;
    }

    /**
     * Amend mapped lead data before pushing to CRM
     *
     * @param $mappedData
     */
    public function amendLeadDataBeforePush(&$mappedData)
    {

    }

    /**
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_id';
    }

    /**
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'client_secret';
    }


    /**
     * {@inheritdoc}
     */
    public function sortFieldsAlphabetically()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string
     */
    public function getFormNotes ($section)
    {
        if ($section == 'field_match') {
            return array('mautic.crm.form.field_match_notes', 'info');
        }

        return parent::getFormNotes($section);
    }
}