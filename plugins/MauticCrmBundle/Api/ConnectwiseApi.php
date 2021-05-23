<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;

/**
 * Class ConnectwiseApi.
 *
 * @property ConnectwiseIntegration $integration
 */
class ConnectwiseApi extends CrmApi
{
    /**
     * @param        $endpoint
     * @param array  $parameters
     * @param string $method
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    protected function request($endpoint, $parameters = [], $method = 'GET')
    {
        $apiUrl = $this->integration->getApiUrl();

        $url = sprintf('%s/%s', $apiUrl, $endpoint);

        $response = $this->integration->makeRequest(
            $url,
            $parameters,
            $method,
            ['encode_parameters' => 'json', 'cw-app-id' => $this->integration->getCompanyCookieKey()]
        );

        $errors = [];
        $code   = 0;

        if (is_array($response)) {
            foreach ($response as $key => $r) {
                $key = preg_replace('/[\r\n]+/', '', $key);
                switch ($key) {
                    case '<!DOCTYPE_html_PUBLIC_"-//W3C//DTD_XHTML_1_0_Strict//EN"_"http://www_w3_org/TR/xhtml1/DTD/xhtml1-strict_dtd"><html_xmlns':
                        $errors[] = '404 not found error';
                        $code     = 404;
                        break;
                    case 'errors':
                        $errors[] = $response['message'];
                        // no break
                    case 'code':
                        $errors[] = $response['message'];
                        break;
                }
            }
        }
        if (!empty($errors)) {
            throw new ApiErrorException(implode(' ', $errors), $code);
        }

        return $response;
    }

    /**
     * @param int $page
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCompanies(array $params, $page = 1)
    {
        $query = [
            'page'     => $page,
            'pageSize' => ConnectwiseIntegration::PAGESIZE,
        ];
        $conditions = isset($params['conditions']) ? $params['conditions'] : [];

        if (isset($params['start'])) {
            $conditions[] = 'lastUpdated > ['.$params['start'].']';
        }

        if ($conditions) {
            $query['conditions'] = implode(' AND ', $conditions);
        }

        return $this->request('company/companies', $query);
    }

    /**
     * @param int $page
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getContacts(array $params, $page = 1)
    {
        $query = [
            'page'     => $page,
            'pageSize' => ConnectwiseIntegration::PAGESIZE,
        ];

        if (isset($params['start'])) {
            $query['conditions'] = 'lastUpdated > ['.$params['start'].']';
        }

        if (isset($params['Email'])) {
            $query['childconditions'] = 'communicationItems/value = "'.$params['Email'].'" AND communicationItems/communicationType="Email"';
        }

        if (isset($params['Ids'])) {
            $query['conditions'] = 'id in ('.$params['Ids'].')';
        }

        return $this->request('company/contacts', $query);
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function createContact(array $params)
    {
        return $this->request('company/contacts', $params, 'POST');
    }

    /**
     * @param $id
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function updateContact(array $params, $id)
    {
        return $this->request('company/contacts/'.$id, $params, 'PATCH');
    }

    /**
     * @return array
     *
     * @throws ApiErrorException
     */
    public function getCampaigns()
    {
        return $this->fetchAllRecords('marketing/groups');
    }

    /**
     * @param     $campaignId
     * @param int $page
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCampaignMembers($campaignId, $page = 1)
    {
        return $this->request('marketing/groups/'.$campaignId.'/contacts', ['page' => $page, 'pageSize' => ConnectwiseIntegration::PAGESIZE]);
    }

    /**
     * https://{connectwiseSite}/v4_6_release/apis/3.0/sales/activities/types.
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function getActivityTypes()
    {
        return $this->fetchAllRecords('sales/activities/types');
    }

    /**
     * @param array $params
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function postActivity($params = [])
    {
        return $this->request('sales/activities', $params, 'POST');
    }

    /**
     * @return array
     *
     * @throws ApiErrorException
     */
    public function getMembers()
    {
        return $this->fetchAllRecords('system/members');
    }

    /**
     * @param $endpoint
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function fetchAllRecords($endpoint)
    {
        $page        = 1;
        $pageSize    = ConnectwiseIntegration::PAGESIZE;
        $allRecords  = [];
        try {
            while ($pagedRecords = $this->request($endpoint, ['page' => $page, 'pageSize' => $pageSize])) {
                $allRecords = array_merge($allRecords, $pagedRecords);
                ++$page;

                if (count($pagedRecords) < $pageSize) {
                    // Received less than page size so we know there are no more records to fetch
                    break;
                }
            }
        } catch (ApiErrorException $exception) {
            if (404 !== $exception->getCode()) {
                // Ignore 404 due to pagination
                throw $exception;
            }
        }

        return $allRecords;
    }
}
