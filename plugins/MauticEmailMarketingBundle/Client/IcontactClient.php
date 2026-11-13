<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Client;

use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * iContact API 2.2.
 */
class IcontactClient
{
    private const string API_URL = 'https://app.icontact.com/icp/a';

    /**
     * Account and client folder IDs looked up per set of credentials.
     *
     * @var array<string, array{string, string}>
     */
    private array $accounts = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @param array<string, mixed> $keys
     *
     * @return array<string, string> list name by list ID
     *
     * @throws ApiException
     */
    public function getLists(array $keys): array
    {
        $response = $this->request($keys, 'GET', $this->getFolderUrl($keys).'/lists');

        $lists = [];
        foreach ($response['lists'] ?? [] as $list) {
            $lists[(string) $list['listId']] = (string) $list['name'];
        }

        return $lists;
    }

    /**
     * @param array<string, mixed> $keys
     *
     * @return array<string, string> public name by custom field ID
     *
     * @throws ApiException
     */
    public function getCustomFields(array $keys): array
    {
        $response = $this->request($keys, 'GET', $this->getFolderUrl($keys).'/customfields');

        $fields = [];
        foreach ($response['customfields'] ?? [] as $field) {
            $fields[(string) $field['customFieldId']] = (string) $field['publicName'];
        }

        return $fields;
    }

    /**
     * @param array<string, mixed>  $keys
     * @param array<string, string> $fields
     *
     * @throws ApiException
     */
    public function subscribe(array $keys, string $listId, array $fields): void
    {
        $folderUrl        = $this->getFolderUrl($keys);
        $fields['status'] = 'normal';

        $contacts = $this->request($keys, 'POST', $folderUrl.'/contacts', [$fields]);

        if (!empty($contacts['contacts'][0]['contactId'])) {
            $this->request($keys, 'POST', $folderUrl.'/subscriptions', [
                [
                    'status'    => 'normal',
                    'listId'    => $listId,
                    'contactId' => $contacts['contacts'][0]['contactId'],
                ],
            ]);
        }
    }

    /**
     * Uses the account and client folder saved by the former integration, or the first ones of the account.
     *
     * @param array<string, mixed> $keys
     *
     * @throws ApiException
     */
    private function getFolderUrl(array $keys): string
    {
        if (!empty($keys['accountId']) && !empty($keys['clientFolderId'])) {
            return sprintf('%s/%s/c/%s', self::API_URL, $keys['accountId'], $keys['clientFolderId']);
        }

        $cacheKey = sha1(serialize([$keys['API-AppId'] ?? '', $keys['API-Username'] ?? '', $keys['API-Password'] ?? '']));
        if (!isset($this->accounts[$cacheKey])) {
            $accounts  = $this->request($keys, 'GET', self::API_URL);
            $accountId = (string) ($accounts['accounts'][0]['accountId'] ?? '');
            if ('' === $accountId) {
                throw new ApiException('No iContact account found for these credentials.');
            }

            $folders  = $this->request($keys, 'GET', self::API_URL.'/'.$accountId.'/c');
            $folderId = (string) ($folders['clientfolders'][0]['clientFolderId'] ?? '');
            if ('' === $folderId) {
                throw new ApiException('No iContact client folder found for these credentials.');
            }

            $this->accounts[$cacheKey] = [$accountId, $folderId];
        }

        return sprintf('%s/%s/c/%s', self::API_URL, ...$this->accounts[$cacheKey]);
    }

    /**
     * @param array<string, mixed>   $keys
     * @param array<int, mixed>|null $json
     *
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    private function request(array $keys, string $method, string $url, ?array $json = null): array
    {
        if (empty($keys['API-AppId']) || empty($keys['API-Username']) || empty($keys['API-Password'])) {
            throw new ApiException('iContact is not configured.');
        }

        $options = [
            'headers' => [
                'Accept'       => 'application/json',
                'Api-Version'  => '2.2',
                'Api-AppId'    => (string) $keys['API-AppId'],
                'Api-Username' => (string) $keys['API-Username'],
                'API-Password' => (string) $keys['API-Password'],
            ],
        ];
        if (null !== $json) {
            $options['json'] = $json;
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $data     = $response->toArray(false);
        } catch (ExceptionInterface $e) {
            throw new ApiException($e->getMessage(), 0, $e);
        }

        if (!empty($data['errors'])) {
            $errors = is_array($data['errors']) ? $data['errors'] : [$data['errors']];
            throw new ApiException(implode(' ', array_map(static fn (mixed $error): string => is_scalar($error) ? (string) $error : (string) json_encode($error), $errors)));
        }

        if ($response->getStatusCode() >= 400) {
            throw new ApiException('iContact returned HTTP '.$response->getStatusCode().'.');
        }

        return $data;
    }
}
