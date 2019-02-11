<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Helper;

use Doctrine\Common\Collections\Collection;
use Joomla\Http\Http;
use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;

class CampaignHelper
{
    /**
     * @var Http
     */
    protected $connector;

    /**
     * Cached contact values in format [contact_id => [key1 => val1, key2 => val1]].
     *
     * @var array
     */
    private $contactsValues = [];

    /**
     * @param Http $connector
     */
    public function __construct(Http $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Prepares the neccessary data transformations and then makes the HTTP request.
     *
     * @param array $config
     * @param Lead  $contact
     */
    public function fireWebhook(array $config, Lead $contact)
    {
        // dump($config);die;
        $payload = $this->getPayload($config, $contact);
        $headers = $this->getHeaders($config, $contact);
        $this->makeRequest($config['url'], $config['method'], $config['timeout'], $headers, $payload);
    }

    /**
     * Gets the payload fields from the config and if there are tokens it translates them to contact values.
     *
     * @param array $config
     * @param Lead  $contact
     *
     * @return array
     */
    private function getPayload(array $config, Lead $contact)
    {
        $payload = !empty($config['additional_data']['list']) ? $config['additional_data']['list'] : '';
        $payload = array_flip(AbstractFormFieldHelper::parseList($payload));

        return $this->getTokenValues($payload, $contact);
    }

    /**
     * Gets the payload fields from the config and if there are tokens it translates them to contact values.
     *
     * @param array $config
     * @param Lead  $contact
     *
     * @return array
     */
    private function getHeaders(array $config, Lead $contact)
    {
        $headers = !empty($config['headers']['list']) ? $config['headers']['list'] : '';
        $headers = array_flip(AbstractFormFieldHelper::parseList($headers));

        return $this->getTokenValues($headers, $contact);
    }

    /**
     * @param string $url
     * @param string $method
     * @param int    $timeout
     * @param array  $headers
     * @param array  $payload
     *
     * @throws \InvalidArgumentException
     * @throws \OutOfRangeException
     */
    private function makeRequest($url, $method, $timeout, array $headers, array $payload)
    {
        switch ($method) {
            case 'get':
                $payload  = $url.(parse_url($url, PHP_URL_QUERY) ? '&' : '?').http_build_query($payload);
                $response = $this->connector->get($payload, $headers, $timeout);
                break;
            case 'post':
            case 'put':
            case 'patch':
                $response = $this->connector->$method($url, $payload, $headers, $timeout);
                break;
            case 'delete':
                $response = $this->connector->delete($url, $headers, $timeout, $payload);
                break;
            default:
                throw new \InvalidArgumentException('HTTP method "'.$method.' is not supported."');
        }

        if (!in_array($response->code, [200, 201])) {
            throw new \OutOfRangeException('Campaign webhook response returned error code: '.$response->code);
        }
    }

    /**
     * Translates tokens to values.
     *
     * @param array $rawTokens
     * @param Lead  $contact
     *
     * @return array
     */
    private function getTokenValues(array $rawTokens, Lead $contact)
    {
        $values        = [];
        $contactValues = $this->getContactValues($contact);

        foreach ($rawTokens as $key => $value) {
            $values[$key] = urldecode(TokenHelper::findLeadTokens($value, $contactValues, true));
        }

        return $values;
    }

    /**
     * Gets array of contact values.
     *
     * @param Lead $contact
     *
     * @return array
     */
    private function getContactValues(Lead $contact)
    {
        if (empty($this->contactsValues[$contact->getId()])) {
            $this->contactsValues[$contact->getId()]              = $contact->getProfileFields();
            $this->contactsValues[$contact->getId()]['ipAddress'] = $this->ipAddressesToCsv($contact->getIpAddresses());
        }

        return $this->contactsValues[$contact->getId()];
    }

    /**
     * @param Collection $ipAddresses
     *
     * @return string
     */
    private function ipAddressesToCsv(Collection $ipAddresses)
    {
        $addresses = [];
        foreach ($ipAddresses as $ipAddress) {
            $addresses[] = $ipAddress->getIpAddress();
        }

        return implode(',', $addresses);
    }
}
