<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class SugarcrmApi extends CrmApi
{
    private $module = 'Leads';

    /**
     * @param        $sMethod
     * @param array  $data
     * @param string $method
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function request($sMethod, $data = [], $method = 'GET')
    {
        $tokenData = $this->integration->getKeys();

        if ($tokenData['version'] == '6') {
            $request_url = sprintf('%s/service/v4_1/rest.php', $tokenData['sugarcrm_url']);

            $sessionParams = array_merge([
                'session'     => $tokenData['id'],
                'module_name' => $this->module,
            ], $data);

            $parameters = [
                'method'        => $sMethod,
                'input_type'    => 'JSON',
                'response_type' => 'JSON',
                'rest_data'     => json_encode($sessionParams),
            ];

            $response = $this->integration->makeRequest($request_url, $parameters, $method);

            if (is_array($response) && !empty($response['name']) && !empty($response['number'])) {
                throw new ApiErrorException($response['name']);
            } else {
                return $response;
            }
        } else {
            $request_url = sprintf('%s/rest/v10/%s', $tokenData['sugarcrm_url'], $sMethod);
            $response    = $this->integration->makeRequest($request_url, $data, $method);

            if (isset($response['error'])) {
                throw new ApiErrorException($response['error_message'], ($response['error'] == 'invalid_grant') ? 1 : 500);
            }

            return $response;
        }
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getLeadFields()
    {
        $tokenData = $this->integration->getKeys();

        if ($tokenData['version'] == '6') {
            return $this->request('get_module_fields');
        } else {
            $parameters = [
                'module_filter' => $this->module,
                'type_filter'   => 'modules',
            ];

            $response = $this->request('metadata', $parameters);

            return $response['modules']['Leads'];
        }
    }

    /**
     * @param array $fields
     *
     * @return array
     *
     * @throws \Mautic\PluginBundle\Exception\ApiErrorException
     */
    public function createLead(array $fields)
    {
        $tokenData = $this->integration->getKeys();

        if ($tokenData['version'] == '6') {
            $leadFields = [];
            foreach ($fields as $name => $value) {
                $leadFields[] = [
                    'name'  => $name,
                    'value' => $value,
                ];
            }
            $parameters = [
                'name_value_list' => $leadFields,
            ];

            return $this->request('set_entry', $parameters, 'POST');
        } else {
            return $this->request('Leads', $fields, 'POST');
        }
    }
}
