<?php
/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      WebMecanik
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticWebinarBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Integration\IntegrationObject;
use Symfony\Component\Form\FormBuilder;

/**
 * Class ConnectwiseIntegration.
 */
class WebikeoIntegration extends WebinarAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Webikeo';
    }
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'JWT';
    }

    /**
     * Get the array key for the auth token.
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'token';
    }

    /**
     * Get the keys for the refresh token and expiry.
     *
     * @return array
     */
    public function getRefreshTokenKeys()
    {
        return ['refresh_token'];
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $inAuthorization
     */
    public function getBearerToken($inAuthorization = false)
    {
        if (!$inAuthorization && isset($this->keys[$this->getAuthTokenKey()])) {
            return $this->keys[$this->getAuthTokenKey()];
        }

        return false;
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://api.webikeo.com/v1';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return $this->getApiUrl().'/login_check';
    }

    /**
     * @return bool
     */
    public function authCallback($settings = [], $parameters = [])
    {
        if ($this->isAuthorized()) {
            return true;
        }
        $autUrl   = $this->getAccessTokenUrl();
        $settings['encode_parameters'] = 'json';
        $parameters['username'] = $this->keys['username'];
        $parameters['password'] = $this->keys['password'];
        $error = false;
        try {
            $response = $this->makeRequest($autUrl, $parameters, 'POST', $settings);
            if (!isset($response['token'])) {
                $error = $response;
            } else {
                $this->keys['token'] = $response['token'];
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $error;
    }


    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized()
    {
        $keys = $this->getKeys();
        return isset($keys[$this->getAuthTokenKey()]);
    }

    /**
     * @param array $filters
     * @return array
     */
    public function getWebinars($filters = [])
    {
        if (empty($filters)) {
            $filters = ['fromDate' => date("Y-m-d H:i:s")];
        }

        $webinars = $this->getApiHelper()->getWebinars($filters);
        $formattedWebinars = [];
        foreach ($webinars as $webinar) {
            if (isset($webinar['id'])) {
                $formattedWebinars[$webinar['id']] = $webinar['title'];
            }
        }

        return $formattedWebinars;
    }

    /**
     * @param $webinar
     * @param Lead $lead
     * @return bool
     */
    public function hasAttendedWebinar($webinar, Lead $lead)
    {
        $filters = ['isNoShow' => false];
        return $this->getWebinarSubscription($webinar, $lead, $filters) ? true : false;
    }

    /**
     * @param $webinar
     * @param Lead $lead
     * @param array $filters
     * @return mixed
     */
    public function getWebinarSubscription($webinar, Lead $lead, $filters = [])
    {
        $leadEmail = $lead->getEmail();

        if ($leadEmail && isset($webinar['id'])) {
            $subscriptions = $this->getApiHelper()->getSubscriptions($webinar, $filters);
            return $this->findSubscriptionByEmail($subscriptions, $leadEmail);
        }

        return null;
    }

    /**
     * @param $subscriptions
     * @param $email
     * @return mixed
     */
    private function findSubscriptionByEmail($subscriptions, $email)
    {
        foreach ($subscriptions as $subscription) {
            if ($subscription['email'] = $email) {
                return $subscription;
            }
        }

        return null;
    }

    /**
     * @param $webinar
     * @param $contact
     * @return bool
     */
    public function subscribeToWebinar($webinar, Lead $contact, $campaign)
    {
        if (!isset($webinar['id'])) {
            return false;
        }

        $contactDataToPost = $this->formatContactData($contact, $campaign);
        $response = $this->getApiHelper()->subscribeContact($webinar['id'], $contactDataToPost);

        return isset($response['source']);
    }

    /**
     * @param Lead $contact
     * @param $campaign
     * @return array
     */
    private function formatContactData(Lead $contact, $campaign)
    {
        return [
            'user' => [
                'email' => $contact->getEmail(),
                'firstName' => $contact->getFirstname(),
                'lastName' => $contact->getLastname(),
                'phone' => $contact->getPhone(),
                'functionLabel' => $contact->getPosition(),
                'companyLabel' => $contact->getCompany()
            ],
            'trackingCampaign' => $campaign
        ];
    }
}