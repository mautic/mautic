<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Integration;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticAddon\MauticCrmBundle\Api\CrmApi;
use MauticAddon\MauticCrmBundle\Api\Exception\ErrorException;

/**
 * Class ZohoIntegration
 */
class ZohoIntegration extends CrmAbstractIntegration
{

    /**
     * Returns the name of the social integration that must match the name of the file
     *
     * @return string
     */
    public function getName ()
    {
        return 'Zoho';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields ()
    {
        return array(
            'email_id' => 'mautic.zoho.form.email',
            'password' => 'mautic.zoho.form.password'
        );
    }

    /**
     * @return string
     */
    public function getClientIdKey ()
    {
        return 'email_id';
    }

    /**
     * @return string
     */
    public function getClientSecreteKey ()
    {
        return 'password';
    }

    /**
     * @return string
     */
    public function getAuthTokenKey ()
    {
        return 'authtoken';
    }

    /**
     * {@inheritdoc}
     */
    public function getOAuthLoginUrl ()
    {
        return $this->factory->getRouter()->generate('mautic_integration_oauth_callback', array('integration' => $this->getName()));
    }

    /**
     * @param array  $parameters
     * @param string $authMethod
     *
     * @return \MauticAddon\MauticCrmBundle\Api\Auth\AbstractAuth|void
     */
    public function createApiAuth ($parameters = array(), $authMethod = 'Auth')
    {
        $zohoSettings = $this->getDecryptedApiKeys();

        parent::createApiAuth($zohoSettings);
    }

    /**
     * Check API Authentication
     */
    public function checkApiAuth ($silenceExceptions = true)
    {
        try {
            if (!$this->auth->isAuthorized()) {
                return false;
            } else {
                return true;
            }
        } catch (ErrorException $exception) {
            $this->logIntegrationError($exception);

            if (!$silenceExceptions) {
                throw $exception;
            }

            return false;
        }
    }

    /**
     * @return array
     */
    public function getAvailableFields ($silenceExceptions = true)
    {
        $zohoFields = array();

        try {
            if ($this->checkApiAuth($silenceExceptions)) {
                $leadObject = CrmApi::getContext($this->getName(), "lead", $this->auth)->getFields('Leads');

                if ($leadObject == null || (isset($leadObject['response']) && isset($leadObject['response']['error']))) {
                    return array();
                }

                $zohoFields = array();
                foreach ($leadObject['Leads']['section'] as $optgroup) {
                    //$zohoFields[$optgroup['dv']] = array();
                    if (!array_key_exists(0, $optgroup['FL']))
                        $optgroup['FL'] = array($optgroup['FL']);
                    foreach ($optgroup['FL'] as $field) {
                        if (!(bool)$field['isreadonly'] || in_array($field['type'], array('Lookup', 'OwnerLookup', 'Boolean'))) {
                            continue;
                        }
                        $key              = InputHelper::alphanum($field['dv']);
                        $zohoFields[$key] = array(
                            'type'  => 'string',
                            'label' => $field['label'],
                            'dv'    => $field['dv']
                        );
                    }
                }
            }
        } catch (ErrorException $exception) {
            $this->logIntegrationError($exception);

            if (!$silenceExceptions) {
                throw $exception;
            }

            return false;
        }

        return $zohoFields;
    }

    /**
     * {@inheritdoc}
     *
     * @param $lead
     *
     * @return array
     */
    public function populateLeadData($lead)
    {
        $mappedData      = parent::populateLeadData($lead);
        $availableFields = $this->getAvailableFields();

        $useToMatch = array();
        foreach ($availableFields as $key => $field) {
            $useToMatch[$key] = $field['dv'];
        }

        $unknown = $this->factory->getTranslator()->trans('mautic.zoho.form.lead.unknown');

        if (empty($mappedData['LastName'])) {
            $mappedData['LastName'] = $unknown;
        }
        if (empty($mappedData['Company'])) {
            $mappedData['Company'] = $unknown;
        }

        $xmlData = '<Leads>';
        $xmlData .= '<row no="1">';
        foreach ($mappedData as $name => $value) {
            if (!isset($useToMatch[$name])) {
                //doesn't seem to exist now
                continue;
            }
            $zohoFieldName = $useToMatch[$name];
            $xmlData      .= sprintf('<FL val="%s"><![CDATA[%s]]></FL>', $zohoFieldName, $value);
        }
        $xmlData .= '</row>';
        $xmlData .= '</Leads>';

        return $xmlData;
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string
     */
    public function getFormNotes($section)
    {
        if ($section == 'field_match') {
            return array('mautic.zoho.form.field_match_notes', 'info');
        }

        return parent::getFormNotes($section);
    }
}
