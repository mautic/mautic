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
use Mautic\PluginBundle\Integration\IntegrationObject;

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
        if ($this->isAuthorized() && !$settings['use_refresh_token']) {
            return true;
        }
        $autUrl                        = $this->getAccessTokenUrl();
        $settings['encode_parameters'] = 'json';
        $parameters['username']        = $this->keys['username'];
        $parameters['password']        = $this->keys['password'];
        $error                         = false;
        try {
            $response = $this->makeRequest($autUrl, $parameters, 'POST', $settings);
            if (!isset($response[$this->getAuthTokenKey()])) {
                $error = $response;
            } else {
                $this->extractAuthKeys($response, $this->getAuthTokenKey());
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $error;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLeadFields($settings = [])
    {
        return [
            'email'              => ['label' => 'Email', 'type' => 'string', 'required' => true],
            'firstName'          => ['label' => 'First Name', 'type' => 'string', 'required' => true],
            'lastName'           => ['label' => 'Last Name', 'type' => 'string', 'required' => true],
            'phone'              => ['label' => 'Phone', 'type' => 'string'],
            'functionLabel'      => ['label' => 'Position', 'type' => 'string'],
            'companyLabel'       => ['label' => 'Company', 'type' => 'string'],
        ];
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    public function getWebinars($filters = [])
    {
        if (empty($filters)) {
            $filters = ['fromDate' => date('Y-m-d H:i:s')];
        }

        $webinars          = $this->getApiHelper()->getWebinars($filters);
        $formattedWebinars = [];
        $webinars          = $webinars['_embedded']['webinar'];

        foreach ($webinars as $webinar) {
            if (isset($webinar['id'])) {
                $formattedWebinars[] = [
                    'value' => $webinar['id'],
                    'label' => $webinar['title'],
                ];
            }
        }

        return $formattedWebinars;
    }

    /**
     * @param $webinar
     * @param Lead $lead
     *
     * @return bool
     */
    public function hasAttendedWebinar($webinar, Lead $lead)
    {
        $filters = ['isNoShow' => true];

        return $this->getWebinarSubscription($webinar, $lead, $filters) ? true : false;
    }

    /**
     * @param $webinar
     * @param Lead  $lead
     * @param array $filters
     *
     * @return mixed
     */
    public function getWebinarSubscription($webinar, Lead $lead, $filters = [])
    {
        $leadEmail = $lead->getEmail();
        if ($leadEmail && isset($webinar['webinar'])) {
            try {
                $subscriptions = $this->getApiHelper()->getSubscriptions($webinar['webinar'], $filters);
            } catch (\Exception $e) {
                return $e->getMessage();
            }

            return $this->findSubscriptionByEmail($subscriptions, $leadEmail);
        }

        return null;
    }

    /**
     * @param $subscriptions
     * @param $email
     *
     * @return mixed
     */
    private function findSubscriptionByEmail($subscriptions, $email)
    {
        if (!isset($subscriptions['_embedded']['subscription'])) {
            return null;
        } else {
            $subscriptions = $subscriptions['_embedded']['subscription'];
        }
        foreach ($subscriptions as $subscription) {
            if ($subscription['user']['email'] == $email) {
                return $subscription;
            }
        }

        return null;
    }

    /**
     * @param $webinar
     * @param $contact
     *
     * @return bool
     */
    public function subscribeToWebinar($webinar, Lead $contact, $campaign)
    {
        if (!isset($webinar['webinar'])) {
            return false;
        }

        $contactDataToPost = $this->formatContactData($contact, $campaign);
        try {
            $response = $this->getApiHelper()->subscribeContact($webinar['webinar'], $contactDataToPost);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return isset($response['source']);
    }

    /**
     * @param Lead $contact
     * @param $campaign
     *
     * @return array
     */
    private function formatContactData(Lead $contact, $campaign)
    {
        $matchedData = $this->populateLeadData($contact);

        return [
            'user'             => $matchedData,
            'trackingCampaign' => $campaign,
        ];
    }

    public function getWebinarAllSubscribers($webinar, $isNoShow = null)
    {
        $filters = [];
        if ($isNoShow !== null) {
            $filters = ['isNoShow' => $isNoShow];
        }

        return $this->getApiHelper()->getSubscriptions($webinar, $filters);
    }

    /**
     * @param $webinar
     * @param null $isNoShow
     * @param $segmentId
     */
    public function getSubscribersForSegmentProcessing($webinar, $isNoShow = null, $segmentId)
    {
        $webinarSubscriberObject = new IntegrationObject('WebinarSubscriber', 'lead');
        $subscriberObject        = new IntegrationObject('Contact', 'lead');
        $subscribers             = $this->getWebinarAllSubscribers($webinar, $isNoShow);
        $subscribers             = isset($subscribers['_embedded']['subscription']) ? $subscribers['_embedded']['subscription'] : [];
        if (empty($subscribers)) {
            return;
        }
        $recordList           = $this->getRecordList($subscribers, 'id');
        $syncedContacts       = $this->integrationEntityModel->getSyncedRecords($subscriberObject, $this->getName(), $recordList);

        // these synced records need to check the id of the segment first
        $existingContactsIds = array_map(
            function ($contact) {
                return ($contact['integration_entity'] == 'Contact') ? $contact['integration_entity_id'] : [];
            },
            $syncedContacts
        );

        $contactsToFetch = array_diff_key($recordList, array_flip($existingContactsIds));

        if (!empty($contactsToFetch)) {
            foreach ($subscribers as $subscriber) {
                if (array_key_exists($subscriber['id'], $contactsToFetch)) {
                    $this->createMauticContact($subscriber);
                }
            }
            $allSubscribers = array_merge($existingContactsIds, array_keys($contactsToFetch));
        } else {
            $allSubscribers = $existingContactsIds;
        }

        $this->saveSyncedWebinarSubscribers($allSubscribers, $webinarSubscriberObject, $webinar, $segmentId);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function createMauticContact($data)
    {
        $executed = 0;

        if (!empty($data)) {
            $webinarObject = new IntegrationObject('Contact', 'lead');

            if (is_array($data)) {
                $id            = $data['id'];
                $formattedData = $this->matchUpData($data['user']);
                $entity        = $this->getMauticLead($formattedData, true);

                if ($entity) {
                    $integrationEntities[] = $this->saveSyncedData($entity, $webinarObject, $id);
                    $this->em->detach($entity);
                    unset($entity);
                    ++$executed;
                }
            }
        }
        if ($integrationEntities) {
            $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($integrationEntities);
            $this->em->clear('Mautic\PluginBundle\Entity\IntegrationEntity');
        }

        return $executed;
    }
}
