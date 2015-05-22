<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticEmailMarketingBundle\Api;

use Mautic\AddonBundle\Exception\ApiErrorException;

class MailchimpApi extends EmailMarketingApi{

    private $version = '2.0';

    protected function request($endpoint, $parameters = array(), $method = 'GET')
    {
        $url = sprintf('%s/%s/%s', $this->keys['api_endpoint'], $this->version, $endpoint);

        $parameters['apikey'] = $this->keys['access_token'];

        $response = $this->integration->makeRequest($url, $parameters, $method, array('encode_parameters' => 'json'));

        if (is_array($response) && !empty($response['status']) && $response['status'] == 'error') {
            throw new ApiErrorException($response['error']);
        } elseif (is_array($response) && !empty($response['errors'])) {
            $errors = array();
            foreach ($response['errors'] as $error) {
                $errors[] = $error['error'];
            }

            throw new ApiErrorException(implode(' ', $errors));
        } else {
            return $response;
        }
    }

    public function getLists()
    {
        return $this->request('lists/list', array('limit' => 100));
    }

    /**
     * @param $listId
     */
    public function getCustomFields($listId)
    {
        return $this->request('lists/merge-vars', array('id' => array($listId)));
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