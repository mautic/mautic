<?php
namespace SugarCRM\Api;

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

        return $response['modules']['Leads'];
    }
}