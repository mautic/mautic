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
        if (isset($this->keys['password'])) {
            $parameters['password'] = $this->keys['password'];
            $parameters['username'] = $this->keys['username'];
        }
        $apiUrl = $this->integration->getApiUrl();

        $url = sprintf('%s/%s', $apiUrl, $endpoint);

        $response = $this->integration->makeRequest($url, $parameters, $method, ['encode_parameters' => 'json', 'cw-app-id' => $this->integration->getCompanyCookieKey()]);

        $errors = [];
        if (is_array($response)) {
            foreach ($response as $key => $r) {
                $key = preg_replace('/[\r\n]+/', '', $key);
                switch ($key) {
                    case '<!DOCTYPE_html_PUBLIC_"-//W3C//DTD_XHTML_1_0_Strict//EN"_"http://www_w3_org/TR/xhtml1/DTD/xhtml1-strict_dtd"><html_xmlns':
                        $errors[] = '404 not found error';
                        break;
                    case 'errors':
                        $errors[] = $response['message'];
                    case 'code':
                        $errors[] = $response['message'];
                        break;
                }
            }
        }
        if (!empty($errors)) {
            throw new ApiErrorException(implode(' ', $errors));
        }

        return $response;
    }

    public function getCompanies($params)
    {
        $lastUpdated = [];
        if (isset($params['start'])) {
            $lastUpdated = ['conditions' => 'lastUpdated > ['.$params['start'].']'];
        }

        return $this->request('company/companies', $lastUpdated);
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getContacts($params)
    {
        $conditions = [];

        if (isset($params['start'])) {
            $conditions = ['conditions' => 'lastUpdated > ['.$params['start'].']'];
        }
        if (isset($params['Email'])) {
            $conditions = ['childconditions' => 'communicationItems/value = "'.$params['Email'].'" AND communicationItems/communicationType="Email"'];
        }

        if (isset($params['Ids'])) {
            $conditions = ['conditions' => 'id in ('.$params['Ids'].')'];
        }

        return $this->request('company/contacts', $conditions);
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function createContact($params)
    {
        return $this->request('company/contacts', $params, 'POST');
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function updateContact($params, $id)
    {
        return $this->request('company/contacts/'.$id, $params, 'PATCH');
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCampaigns()
    {
        return $this->request('marketing/groups');
    }

    /**
     * @param $campaignId
     * @param $params
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCampaignMembers($campaignId)
    {
        return $this->request('marketing/groups/'.$campaignId.'/contacts');
    }

    /**
     * https://{connectwiseSite}/v4_6_release/apis/3.0/sales/activities/types.
     *
     * @param array $params
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getActivityTypes($params = [])
    {
        return $this->request('sales/activities/types');
    }

    public function postActivity($params = [])
    {
        return $this->request('sales/activities', $params, 'POST');
    }

    public function getMembers($params = [])
    {
        return $this->request('system/members');
    }
}
