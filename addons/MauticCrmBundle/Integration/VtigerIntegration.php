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
 * Class VtigerIntegration
 */
class VtigerIntegration extends CrmAbstractIntegration
{
    /**
     * Returns the name of the social integration that must match the name of the file
     *
     * @return string
     */
    public function getName()
    {
        return 'Vtiger';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            'url'           => 'mautic.vtiger.form.url',
            'username'      => 'mautic.vtiger.form.username',
            'access_key'    => 'mautic.vtiger.form.password'
        );
    }

    /**
    {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @param array  $parameters
     * @param string $authMethod
     *
     * @return \MauticAddon\MauticCrmBundle\Api\Auth\AuthInterface|void
     */
    public function createApiAuth($parameters = array(), $authMethod = 'Auth')
    {
        $vtigerSettings = $this->settings->getApiKeys();

        parent::createApiAuth($vtigerSettings);
    }

    /**
     * Check API Authentication
     */
    public function checkApiAuth()
    {
        try {
            if (!$this->isAuthorized()) {
                return false;
            }
            return true;
        } catch (ErrorException $exception) {
            return false;
        }
    }

    /**
     * @return mixed|void
     */
    public function getAvailableFields()
    {
        $vtigerFields = array();

        if ($this->checkApiAuth()) {
            $leadObject = CrmApi::getContext($this->getName(), "lead", $this->auth)->describe("Leads");

            foreach ($leadObject['fields'] as $fieldInfo) {
                if (!isset($fieldInfo['name']))
                    continue;
                $vtigerFields[$fieldInfo['name']] = array("type" => "string");
            }
        }

        return $vtigerFields;
    }

    /**
     * @param $data
     * @return mixed|void
     */
    public function create(MauticFactory $factory, $data)
    {

    }
}
