<?php
namespace MauticAddon\MauticCrmBundle\Crm\Vtiger\Api\Object;

use MauticAddon\MauticCrmBundle\Api\CrmApi;
use MauticAddon\MauticCrmBundle\Api\Exception\ErrorException;

class Lead extends CrmApi
{
    protected $element = "Leads";

    public function request($operation, $element, $elementData = array(), $method = 'GET', $retry = false)
    {
        $tokenData = $this->auth->getAccessTokenData();

        $request_url = sprintf('%s/webservice.php', $tokenData['url']);
        $parameters  = array(
            'operation'   => $operation,
            'sessionName' => $tokenData['sessionName'],
            'elementType' => $element
        );

        if (!empty($elementData)) {
            $parameters['element'] = json_encode($elementData);
        }
        $response = $this->auth->makeRequest($request_url, $parameters, $method);

        if (!empty($response['error'])) {
            //Has the session expired?
            if ($response['error']['code'] == 'INVALID_SESSIONID' && !$retry) {
                //try to revalidate
                if ($this->integration->authorizeApi()) {
                    $this->integration->persistIntegrationSettings();

                    //now retry
                    return $this->request($operation, $element, $elementData, $method, true);
                }
            }

            throw new ErrorException($response['error']['message']);
        }

        return $response['result'];
    }

    /**
     * List types
     *
     * @return mixed
     */
    public function listTypes ()
    {
        return $this->request('listtypes', $this->element);
    }

    /**
     * List leads
     *
     * @return mixed
     */
    public function describe ()
    {
        return $this->request('describe', $this->element);
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function create (array $data)
    {
        return $this->request('create', $this->element, $data, 'POST');
    }
}