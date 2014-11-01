<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Integration;

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