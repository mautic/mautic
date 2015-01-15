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
    public function getClientSecretKey ()
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
                            'type'     => 'string',
                            'label'    => $field['label'],
                            'dv'       => $field['dv'],
                            'required' => ($field['req'] == 'true')
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
     * @param $key
     * @param $field
     *
     * @return mixed
     */
    public function convertLeadFieldKey($key, $field)
    {
        return $field['dv'];
    }

    /**
     * {@inheritdoc}
     *
     * @param $lead
     *
     * @return array
     */
    public function populateLeadData ($lead)
    {
        $mappedData = parent::populateLeadData($lead);

        $xmlData = '<Leads>';
        $xmlData .= '<row no="1">';
        foreach ($mappedData as $name => $value) {
            $xmlData .= sprintf('<FL val="%s"><![CDATA[%s]]></FL>', $mappedData, $value);
        }
        $xmlData .= '</row>';
        $xmlData .= '</Leads>';

        return $xmlData;
    }
}
