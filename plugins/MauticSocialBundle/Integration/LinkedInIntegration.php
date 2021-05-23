<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Integration;

use MauticPlugin\MauticSocialBundle\Form\Type\LinkedInType;

/**
 * Class LinkedInIntegration.
 */
class LinkedInIntegration extends SocialIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'LinkedIn';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return [
            'share_button',
            'login_button',
            'public_profile',
        ];
    }

    /**
     * @return string
     */
    public function getAuthScope()
    {
        return 'r_emailaddress';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationUrl()
    {
        return 'https://www.linkedin.com/uas/oauth2/authorization';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenUrl()
    {
        return 'https://www.linkedin.com/uas/oauth2/accessToken';
    }

    /**
     * @param $endpoint
     *
     * @return string
     */
    public function getApiUrl($endpoint)
    {
        return "https://api.linkedin.com/$endpoint";
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFields()
    {
        return [
            'linkedin',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return [
            'client_id'     => 'mautic.integration.keyfield.clientid',
            'client_secret' => 'mautic.integration.keyfield.clientsecret',
        ];
    }

    /**
     * Get public data.
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return array
     */
    public function getUserData($identifier, &$socialCache)
    {
        $persistLead = false;
        $accessToken = $this->getContactAccessToken($socialCache);

        if (!isset($accessToken['access_token'])) {
            return;
        } elseif (isset($accessToken['access_token'])) {
            $persistLead = $this->persistNewLead;
        }

        $fields        = $this->getFieldNames($this->getAvailableLeadFields());
        $profileFields = implode(',', array_keys($fields));
        $url           = $this->getApiUrl('v1/people/~:(id,picture-url,'.$profileFields.')');
        $parameters    = [
            'oauth2_access_token' => $accessToken['access_token'],
            'format'              => 'json',
        ];

        $data = $this->makeRequest($url, $parameters, 'GET', ['auth_type' => 'rest']);

        if (true) {
            $info = $this->matchUpData($data);

            if (isset($data->publicProfileUrl)) {
                $info['profileHandle'] = str_replace('https://www.linkedin.com/', '', $data->publicProfileUrl);
            }

            $info['profileImage'] = preg_replace('/\?.*/', '', $data->pictureUrl);

            $socialCache['profile']     = $info;
            $socialCache['lastRefresh'] = new \DateTime();
            $socialCache['accessToken'] = $this->encryptApiKeys($accessToken);

            $this->getMauticLead($info, $persistLead, $socialCache, $identifier);

            return $data;
        }

        return null;
    }

    /**
     * gets the name of field to sent to linked in API to fetch social profile data.
     *
     * @param $leadFields
     *
     * @return array
     */
    protected function getFieldNames($leadFields)
    {
        $fields = [];
        foreach ($leadFields as $leadField) {
            $fields[$leadField['fieldName']] = $leadField['fieldName'];
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function cleanIdentifier($identifier)
    {
        return $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLeadFields($settings = [])
    {
        // Until lead profile support is restored
        //return array();

        return [
            'firstName'     => ['type' => 'string', 'fieldName' => 'first-name'],
            'lastName'      => ['type' => 'string', 'fieldName' => 'last-name'],
            'maidenName'    => ['type' => 'string', 'fieldName' => 'maiden-name'],
            'formattedName' => ['type' => 'string', 'fieldName' => 'formatted-name'],
            'headline'      => ['type' => 'string', 'fieldName' => 'headline'],
            'location'      => [
                'type'   => 'object',
                'fields' => [
                    'name',
                ],
                'fieldName' => 'location',
            ],
            'summary'     => ['type' => 'string', 'fieldName' => 'summary'],
            'specialties' => ['type' => 'string', 'fieldName' => 'specialties'],
            'positions'   => [
                'type'   => 'array_object',
                'fields' => [
                    'values',
                ],
                'fieldName' => 'positions',
            ],
            'publicProfileUrl' => ['type' => 'string', 'fieldName' => 'public-profile-url'],
            'emailAddress'     => ['type' => 'string', 'fieldName' => 'email-address'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return LinkedInType::class;
    }
}
