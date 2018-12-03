<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;
use MauticPlugin\MauticCrmBundle\Services\TransportInterface;
use Psr\Http\Message\ResponseInterface;

class PipedriveApi extends CrmApi
{
    const ORGANIZATIONS_API_ENDPOINT = 'organizations';
    const PERSONS_API_ENDPOINT       = 'persons';
    const USERS_API_ENDPOINT         = 'users';
    /**
     * @var TransportInterface
     */
    private $transport;

    private $apiFields = [];

    /**
     * PipedriveApi constructor.
     *
     * @param CrmAbstractIntegration $integration
     * @param TransportInterface     $transport
     */
    public function __construct(CrmAbstractIntegration $integration, TransportInterface $transport)
    {
        $this->transport = $transport;

        parent::__construct($integration);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function createCompany(array $data = [])
    {
        $params   = $this->getRequestParameters($data);
        $url      = sprintf('%s/%s', $this->integration->getApiUrl(), self::ORGANIZATIONS_API_ENDPOINT);
        $response = $this->transport->post($url, $params);

        return $this->getResponseData($response);
    }

    /**
     * @param array $data
     * @param null  $id
     *
     * @return array
     */
    public function updateCompany(array $data = [], $id = null)
    {
        $params   = $this->getRequestParameters($data);
        $url      = sprintf('%s/%s/%s', $this->integration->getApiUrl(), self::ORGANIZATIONS_API_ENDPOINT, $id);
        $response = $this->transport->put($url, $params);

        return $this->getResponseData($response);
    }

    /**
     * @param array $data
     * @param null  $id
     *
     * @return array
     */
    public function removeCompany($id = null)
    {
        $params   = $this->getRequestParameters();
        $url      = sprintf('%s/%s/%s', $this->integration->getApiUrl(), self::ORGANIZATIONS_API_ENDPOINT, $id);
        $response = $this->transport->delete($url, $params);

        return $this->getResponseData($response);
    }

    /**
     * @param $data
     */
    public function createLead(array $data = [])
    {
        $params   = $this->getRequestParameters($data);
        $url      = sprintf('%s/%s', $this->integration->getApiUrl(), self::PERSONS_API_ENDPOINT);
        $response = $this->transport->post($url, $params);

        return $this->getResponseData($response);
    }

    /**
     * @param $data
     */
    public function updateLead(array $data, $id)
    {
        $params   = $this->getRequestParameters($data);
        $url      = sprintf('%s/%s/%s', $this->integration->getApiUrl(), self::PERSONS_API_ENDPOINT, $id);
        $response = $this->transport->put($url, $params);

        return $this->getResponseData($response);
    }

    /**
     * @param $data
     */
    public function deleteLead($id)
    {
        $params   = $this->getRequestParameters();
        $url      = sprintf('%s/%s/%s', $this->integration->getApiUrl(), self::PERSONS_API_ENDPOINT, $id);
        $response = $this->transport->delete($url, $params);

        return $this->getResponseData($response);
    }

    /**
     * @param string $email
     *
     * @return array
     */
    public function findByEmail($email)
    {
        $url = sprintf('%s/%s/find', $this->integration->getApiUrl(), self::PERSONS_API_ENDPOINT);

        $params = [
            'query' => array_merge($this->getAuthQuery(), [
                'term'            => $email,
                'search_by_email' => true,
            ]),
        ];

        $response = $this->transport->get($url, $params);

        return $this->getResponseData($response);
    }

    /**
     * @param     $name
     * @param int $start
     * @param int $limit
     *
     * @return array
     */
    public function findCompanyByName($name, $start = 0, $limit = 10)
    {
        $url = sprintf('%s/%s/find', $this->integration->getApiUrl(), self::ORGANIZATIONS_API_ENDPOINT);

        $params = [
            'query' => array_merge($this->getAuthQuery(), [
                'term'            => $name,
                'start'           => $start,
                'limit'           => $limit,
            ]),
        ];

        $response = $this->transport->get($url, $params);

        return $this->getResponseData($response);
    }

    /**
     * @param array  $query
     * @param string $endpoint
     *
     * @return mixed
     */
    public function getDataByEndpoint(array $query, $endpoint)
    {
        $params = [
            'query' => array_merge($this->getAuthQuery(), $query),
        ];

        $url = sprintf('%s/%s', $this->integration->getApiUrl(), $endpoint);

        $response = $this->transport->get($url, $params);

        return json_decode($response->getBody(), true);
    }

    /**
     * @param array $objects
     *
     * @return array
     */
    public function getFields($object = null)
    {
        if (!empty($this->apiFields[$object])) {
            return $this->apiFields[$object];
        }

        $params = [
            'query' => $this->getAuthQuery(),
        ];

        $url = sprintf('%s/%sFields', $this->integration->getApiUrl(), $object);

        $response = $this->transport->get($url, $params);

        $this->apiFields[$object] = $response;

        $data = $this->getResponseData($response);

        return !empty($data) ? $data : [];
    }

    /**
     * @param ResponseInterface $response
     *
     * @return array
     */
    private function getResponseData(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        return isset($body['data']) ? $body['data'] : [];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function getRequestParameters(array $data = [])
    {
        foreach ($data as $k => $d) {
            $data[$k] = str_replace('|', ',', $d);
        }

        return [
            'form_params' => $data,
            'query'       => $this->getAuthQuery(),
        ];
    }

    /**
     * @return array
     */
    private function getAuthQuery()
    {
        $tokenData = $this->integration->getKeys();

        return [
            'api_token' => $tokenData[$this->integration->getAuthTokenKey()],
        ];
    }
}
