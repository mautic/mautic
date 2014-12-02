<?php
namespace MauticAddon\MauticCrmBundle\Crm\Sugarcrm\Api\Object;

use MauticAddon\MauticCrmBundle\Api\CrmApi;
use MauticAddon\MauticCrmBundle\Api\Exception\ErrorException;

class Lead extends CrmApi
{
    /**
     * @param $Object
     * @return mixed
     * @todo Replace this request later
     */
    public function getInfo($Object)
    {
        $tokenData = $this->auth->getAccessTokenData();
        $parameters = array(
            'module_filter' => 'Leads',
            'type_filter'=> 'modules'
        );
        $request_url = sprintf('%s/rest/v10/metadata',$tokenData['sugarcrm_url']);
        $response = $this->auth->makeRequest($request_url, $parameters);

        if (isset($response['error'])) {
            throw new ErrorException($response['error_message'],($response['error'] == 'invalid_grant') ? 1 : 500);
        }

        return $response['modules']['Leads'];
    }

    /**
     * @param $module
     * @param array $fields
     * @return array
     * @throws \MauticAddon\MauticCrmBundle\Api\Exception\ErrorException
     */
    public function create($module, array $fields)
    {
        $tokenData = $this->auth->getAccessTokenData();
        $parameters = json_encode($fields);
        $request_url = sprintf('%s/rest/v10/%s',$tokenData['sugarcrm_url'], $module);
        $response = $this->auth->makeRequest($request_url, $parameters);

        if (isset($response['error'])) {
            throw new ErrorException($response['error_message'],($response['error'] == 'invalid_grant') ? 1 : 500);
        }

        return $response;
    }
}