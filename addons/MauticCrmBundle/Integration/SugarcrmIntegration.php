<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Integration;
use MauticAddon\MauticCrmBundle\Api\CrmApi;
use MauticAddon\MauticCrmBundle\Api\Exception\ErrorException;

/**
 * Class SugarcrmIntegration
 */
class SugarcrmIntegration extends CrmAbstractIntegration
{
    /**
     * @var \MauticAddon\MauticCrmBundle\Crm\Sugarcrm\Api\Auth\Auth
     */
    protected $auth;

    /**
     * Returns the name of the social integration that must match the name of the file
     *
     * @return string
     */
    public function getName()
    {
        return 'Sugarcrm';
    }

    /**
     * {@inheritdoc}
     */
    public function getOAuthLoginUrl()
    {
        return $this->factory->getRouter()->generate('mautic_integration_oauth_callback', array('integration' => $this->getName()));
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            'url'           => 'mautic.sugarcrm.form.url',
            'client_key'    => 'mautic.sugarcrm.form.clientkey',
            'client_secret' => 'mautic.sugarcrm.form.clientsecret',
            'username'      => 'mautic.sugarcrm.form.username',
            'password'      => 'mautic.sugarcrm.form.password'
        );
    }

    /**
     * @param array  $parameters
     * @param string $authMethod
     *
     * @return \MauticAddon\MauticCrmBundle\Api\Auth\AbstractAuth|void
     */
    public function createApiAuth($parameters = array(), $authMethod = 'Auth')
    {
        $sugarCRMSettings                    = $this->getDecryptedApiKeys();
        $sugarCRMSettings['callback']        = $this->getOauthCallbackUrl();
        if (isset($sugarCRMSettings['url'])) {
            $sugarCRMSettings['requestTokenUrl'] = sprintf('%s/rest/v10/oauth2/token', $sugarCRMSettings['url']);
        }

        parent::createApiAuth($sugarCRMSettings);
    }

    /**
     * Check API Authentication
     */
    public function checkApiAuth($silenceExceptions = true)
    {
        $sugarCRMSettings = $this->getDecryptedApiKeys();
        if (!isset($sugarCRMSettings['url'])) {
            return false;
        }

        return parent::checkApiAuth($silenceExceptions);
    }

    /**
     * @return array|mixed
     */
    public function getAvailableFields($silenceExceptions = true)
    {
        $sugarFields = array();

        try {
            if ($this->checkApiAuth($silenceExceptions)) {
                $leadObject  = CrmApi::getContext($this->getName(), "lead", $this->auth)->getInfo();
                $sugarFields = array();
                foreach ($leadObject['fields'] as $fieldInfo) {
                    if (isset($fieldInfo['name']) && empty($fieldInfo['readonly']) && !empty($fieldInfo['comment']) && !in_array($fieldInfo['type'], array('id', 'team_list', 'bool', 'link', 'relate'))) {
                        $sugarFields[$fieldInfo['name']] = array('type' => 'string', 'label' => $fieldInfo['comment']);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $sugarFields;
    }
}
