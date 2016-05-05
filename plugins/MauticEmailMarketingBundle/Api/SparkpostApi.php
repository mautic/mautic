<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEmailMarketingBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class SparkpostApi extends EmailMarketingApi{

    private $version = '1.0';

    /**
     * @param        $endpoint
     * @param array  $parameters
     * @param string $method
     *
     * @return mixed|string
     * @throws ApiErrorException
     */
    protected function request($resource, $parameters = array(), $method = 'GET')
    {
        $apiEndpoint = 'https://api.sparkpost.com/api/v1';
        $url         = $apiEndpoint . "/" . $resource;

        $response = $this->integration->makeRequest(
            $url,
            $parameters,
            $method,
            array(
                'encode_parameters' => 'json',
                'headers'           => array(
                    'Authorization' => $this->keys['api_key'],
                    'Accept'        => 'application/json'
                )
            )
        );

        if (is_array($response) && !empty($response['status']) && $response['status'] == 'error') {
            throw new ApiErrorException($response['error']);
        } elseif (is_array($response) && !empty($response['errors'])) {
            $errors = array();
            foreach ($response['errors'] as $error) {
                $errors[] = $error['message'];
            }
            throw new ApiErrorException(implode(' ', $errors));
        } else {
            return $response;
        }
    }

    public function getLists()
    {
        return $this->request('recipient-lists');
    }

    /**
     * @param $listId
     *
     * @return mixed|string
     * @throws ApiErrorException
     */
    public function getCustomFields($listId)
    {
        //TODO: Figure out if this is actually needed or not!
        throw new ApiErrorException($listId);
        //return $this->request('lists/merge-vars', array('id' => array($listId)));
    }

    /**
     * @param       $email
     * @param       $listId
     * @param array $fields
     * @param array $config
     *
     * @return mixed|string
     * @throws ApiErrorException
     */
    public function subscribeLead($email, $listId, $fields = array(), $config = array())
    {
        $emailStruct        = new \stdClass();
        $emailStruct->email = $email;

        $parameters = array_merge($config, array(
            'id'           => $listId,
            'merge_vars'   => $fields,
        ));
        $parameters['email'] = $emailStruct;

        return $this->request('lists/subscribe', $parameters, 'POST');
    }
}