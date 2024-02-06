<?php

namespace Mautic\WebhookBundle\Helper;

use Doctrine\Common\Collections\Collection;
use GuzzleHttp\Client;
use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\WebhookBundle\Event\WebhookRequestEvent;
use Mautic\WebhookBundle\WebhookEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CampaignHelper
{
    /**
     * Cached contact values in format [contact_id => [key1 => val1, key2 => val1]].
     */
    private array $contactsValues = [];

    public function __construct(
        protected Client $client,
        protected CompanyModel $companyModel,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * Prepares the neccessary data transformations and then makes the HTTP request.
     */
    public function fireWebhook(array $config, Lead $contact): void
    {
        $payload = $this->getPayload($config, $contact);
        $headers = $this->getHeaders($config, $contact);
        $url     = rawurldecode(TokenHelper::findLeadTokens($config['url'], $this->getContactValues($contact), true));

        $webhookRequestEvent = new WebhookRequestEvent($contact, $url, $headers, $payload);
        $this->dispatcher->dispatch($webhookRequestEvent, WebhookEvents::WEBHOOK_ON_REQUEST);

        $this->makeRequest(
            $webhookRequestEvent->getUrl(),
            $config['method'],
            $config['timeout'],
            $webhookRequestEvent->getHeaders(),
            $webhookRequestEvent->getPayload()
        );
    }

    /**
     * Gets the payload fields from the config and if there are tokens it translates them to contact values.
     */
    private function getPayload(array $config, Lead $contact): array
    {
        $payload = !empty($config['additional_data']['list']) ? $config['additional_data']['list'] : '';
        $payload = array_flip(AbstractFormFieldHelper::parseList($payload));

        return $this->getTokenValues($payload, $contact);
    }

    /**
     * Gets the payload fields from the config and if there are tokens it translates them to contact values.
     */
    private function getHeaders(array $config, Lead $contact): array
    {
        $headers = !empty($config['headers']['list']) ? $config['headers']['list'] : '';
        $headers = array_flip(AbstractFormFieldHelper::parseList($headers));

        return $this->getTokenValues($headers, $contact);
    }

    /**
     * @param string $url
     * @param string $method
     * @param int    $timeout
     *
     * @throws \InvalidArgumentException
     * @throws \OutOfRangeException
     */
    private function makeRequest($url, $method, $timeout, array $headers, array $payload): void
    {
        switch ($method) {
            case 'get':
                $payload  = $url.(parse_url($url, PHP_URL_QUERY) ? '&' : '?').http_build_query($payload);
                $response = $this->client->get($payload, [
                    \GuzzleHttp\RequestOptions::HEADERS => $headers,
                    \GuzzleHttp\RequestOptions::TIMEOUT => $timeout,
                ]);
                break;
            case 'post':
            case 'put':
            case 'patch':
                $headers  = array_change_key_case($headers);
                $options  = [
                    \GuzzleHttp\RequestOptions::HEADERS     => $headers,
                    \GuzzleHttp\RequestOptions::TIMEOUT     => $timeout,
                ];
                if (array_key_exists('content-type', $headers) && 'application/json' == strtolower($headers['content-type'])) {
                    $options[\GuzzleHttp\RequestOptions::BODY] = json_encode($payload);
                } else {
                    $options[\GuzzleHttp\RequestOptions::FORM_PARAMS] = $payload;
                }
                $response = $this->client->request($method, $url, $options);
                break;
            case 'delete':
                $response = $this->client->delete($url, [
                    \GuzzleHttp\RequestOptions::HEADERS => $headers,
                    \GuzzleHttp\RequestOptions::TIMEOUT => $timeout,
                ]);
                break;
            default:
                throw new \InvalidArgumentException('HTTP method "'.$method.' is not supported."');
        }

        if (!in_array($response->getStatusCode(), [200, 201])) {
            throw new \OutOfRangeException('Campaign webhook response returned error code: '.$response->getStatusCode());
        }
    }

    /**
     * Translates tokens to values.
     */
    private function getTokenValues(array $rawTokens, Lead $contact): array
    {
        $values        = [];
        $contactValues = $this->getContactValues($contact);

        foreach ($rawTokens as $key => $value) {
            $values[$key] = rawurldecode(TokenHelper::findLeadTokens($value, $contactValues, true));
        }

        return $values;
    }

    /**
     * Gets array of contact values.
     *
     * @return array
     */
    private function getContactValues(Lead $contact)
    {
        if (empty($this->contactsValues[$contact->getId()])) {
            $this->contactsValues[$contact->getId()]              = $contact->getProfileFields();
            $this->contactsValues[$contact->getId()]['ipAddress'] = $this->ipAddressesToCsv($contact->getIpAddresses());
            $this->contactsValues[$contact->getId()]['companies'] = $this->companyModel->getRepository()->getCompaniesByLeadId($contact->getId());
        }

        return $this->contactsValues[$contact->getId()];
    }

    private function ipAddressesToCsv(Collection $ipAddresses): string
    {
        $addresses = [];
        foreach ($ipAddresses as $ipAddress) {
            $addresses[] = $ipAddress->getIpAddress();
        }

        return implode(',', $addresses);
    }
}
