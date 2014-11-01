<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Integration;
use Mautic\MapperBundle\Helper\ApiHelper;

/**
 * Class SugarCRMIntegration
 * @package Mautic\MapperBundle\Integration
 */
class SugarCRMIntegration extends AbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAppAlias()
    {
        return 'sugarcrm';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAppName()
    {
        return 'Sugar CRM';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getImage()
    {
        return 'app/bundles/MapperBundle/Assets/images/applications/sugarcrm_128.png';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSupportedObjects()
    {
        return array(
            'Leads'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param $objectName
     */
    public function getMauticObject($objectName)
    {
        $supportedObjects = $this->getSupportedObjects();

        switch ($objectName)
        {
            case $supportedObjects[0]:
                $entities = $this->factory->getModel('lead.field')->getEntities();
                break;
        }

        return $entities;
    }

    /**
     * {@inheritdoc}
     *
     * @param $objectName
     */
    public function getApiObject($objectName)
    {
        $sugarAuth = $this->getApiAuth();
        ApiHelper::checkApiAuthentication($this->factory, $this->getAppAlias(), $this);
        $supportedObjects = $this->getSupportedObjects();

        switch ($objectName)
        {
            case $supportedObjects[0]:
                $objectResponse = \SugarCRM\SugarCRMApi::getContext("object", $sugarAuth)->getInfo($objectName);
                break;
        }

        return $objectResponse;
    }

    /**
     * Convert Response from a array option: text, value
     *
     * @param $objectName
     * @param $response
     * @return array
     */
    public function getObjectOptions($objectName, $response)
    {
        $supportedObjects = $this->getSupportedObjects();

        $options = array();
        switch ($objectName)
        {
            case $supportedObjects[0]:
                foreach ($response['fields'] as $field) {
                    if (!isset($field['name'])) continue;
                    $option  = array(
                        'value' => $field['name'],
                        'text' => $field['name']
                    );
                    $options[] = $option;
                }
                break;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     *
     * @param null $request
     * @return array|string
     */
    public function getSettings($request = null)
    {
        $fields = array(
            'clientKey',
            'clientSecret',
            'url',
            'username',
            'password',
            'accessToken',
            'accessTokenExpires'
        );
        $data = $this->getEntity()->getApiKeys();
        if (!empty($request)) {
            foreach ($fields as $field) {
                if (isset($request[$field]) && !empty($request[$field])) {
                    $data[$field] = $request[$field];
                }
            }
        }

        $settings = array(
            'clientKey'          => !empty($data['clientKey']) ? $data['clientKey'] : '' ,
            'clientSecret'       => !empty($data['clientSecret']) ? $data['clientSecret'] : '' ,
            'callback'           => $this->getCallbackLink(),
            'url'                => !empty($data['url']) ? $data['url'] : '' ,
            'username'           => !empty($data['username']) ? $data['username'] : '' ,
            'password'           => !empty($data['password']) ? $data['password'] : '' ,
            'accessToken'        => !empty($data['accessToken']) ? $data['accessToken'] : '' ,
            'accessTokenExpires' => !empty($data['accessTokenExpires']) ? $data['accessTokenExpires'] : '' ,
            'requestTokenUrl'    => !empty($data['url']) ? sprintf('%s/rest/v10/oauth2/token', $data['url']) : ''
        );

        return $settings;
    }
}