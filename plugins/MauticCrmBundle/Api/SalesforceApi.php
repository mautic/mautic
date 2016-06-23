<?php
namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class SalesforceApi extends CrmApi
{
    protected $object  = 'Lead';
    protected $requestSettings = array(
        'encode_parameters' => 'json'
    );

    public function request($operation, $elementData = array(), $method = 'GET', $retry = false)
    {
        $request_url = sprintf($this->integration->getApiUrl() . '/%s/%s', $this->object, $operation);

        $response = $this->integration->makeRequest($request_url, $elementData, $method, $this->requestSettings);

        if (!empty($response['errors'])) {
            throw new ApiErrorException(implode(', ', $response['errors']));
        } elseif (is_array($response)) {
            $errors = array();
            foreach ($response as $r) {
                if (is_array($r) && !empty($r['errorCode']) && !empty($r['message'])) {
                    // Check for expired session then retry if we can refresh
                    if ($r['errorCode'] == 'INVALID_SESSION_ID' && !$retry) {
                        $refreshError = $this->integration->authCallback(array('use_refresh_token' => true));

                        if (empty($refreshError)) {
                            return $this->request($operation, $elementData, $method, true);
                        }
                    }

                    $errors[] = $r['message'];
                }
            }

            if (!empty($errors)) {
                throw new ApiErrorException(implode(', ', $errors));
            }
        }

        return $response;
    }

    /**
     * @return mixed
     */
    public function getLeadFields()
    {
        return $this->request('describe');
    }

    /**
     * Creates Salesforce lead
     *
     * @param array $data
     *
     * @return mixed
     */
    public function createLead(array $data)
    {
        return $this->request('', $data, 'POST');
    }

    /**
     * Get Salesforce leads
     *
     * @param string $query
     *
     * @return mixed
     */
    public function getLeads($query)
    {
        return $this->request('updated/', $query);
    }

    /**
     * Get Salesforce leads
     *
     * @param string $query
     *
     * @return mixed
     */
    public function getSalesForceLeadById($id, $params)
    {
        return $this->request($id.'/',$params);
    }
}