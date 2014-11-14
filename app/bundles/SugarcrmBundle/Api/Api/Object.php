<?php
namespace Mautic\SugarcrmBundle\Api\Api;

use Mautic\SugarcrmBundle\Api\Exception\ErrorException;

class Object extends Api
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
}