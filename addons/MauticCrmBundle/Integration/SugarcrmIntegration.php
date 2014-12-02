<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Integration;

/**
 * Class SugarcrmIntegration
 */
class SugarcrmIntegration extends CrmAbstractIntegration
{

    /**
     * Returns the name of the social integration that must match the name of the file
     *
     * @return string
     */
    public function getName()
    {
        return 'Sugarcrm';
    }

    public function getClientIdKey()
    {
        return 'client_key';
    }

    public function getClientSecreteKey()
    {
        return 'clientSecret';
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
     * @return \MauticAddon\MauticCrmBundle\Api\Auth\AuthInterface|void
     */
    public function createApiAuth($parameters = array(), $authMethod = 'Auth')
    {
        $sugarCRMSettings                    = $this->settings->getApiKeys();
        $sugarCRMSettings['callback']        = $this->getOauthCallbackUrl();
        if (isset($sugarCRMSettings['url'])) {
            $sugarCRMSettings['requestTokenUrl'] = sprintf('%s/rest/v10/oauth2/token', $sugarCRMSettings['url']);
        }

        parent::createApiAuth($sugarCRMSettings);
    }

    /**
     * Check API Authentication
     */
    public function checkApiAuth()
    {
        $sugarCRMSettings = $this->settings->getApiKeys();
        if (!isset($sugarCRMSettings['url'])) {
            return false;
        }

        try {
            if (!$this->auth->isAuthorized()) {
                return false;
            }
            return true;
        } catch (ErrorException $exception) {
            return false;
        }
    }

    /**
     * @return array|mixed
     */
    public function getAvailableFields()
    {
        $sugarFields = array();

        if ($this->checkApiAuth()) {
            $leadObject = CrmApi::getContext($this->getName(), "lead", $this->auth)->getInfo("Leads");
            $sugarFields = array();
            foreach ($leadObject['fields'] as $fieldInfo) {
                if (!isset($fieldInfo['name']))
                    continue;
                $sugarFields[$fieldInfo['name']] = array("type" => "string");
            }
        }

        return $sugarFields;
    }

    /**
     * @param $data
     * @return mixed|void
     */
    public function create(MauticFactory $factory, $data)
    {

    }
}
