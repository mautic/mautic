<?php

namespace MauticPlugin\MauticFullContactBundle\Services;

use MauticPlugin\MauticFullContactBundle\Exception\ApiException;
use MauticPlugin\MauticFullContactBundle\Exception\BaseException;

class FullContact_API extends FullContact_Person
{
    public function __construct(string $api_key)
    {
        parent::__construct($api_key);
        trigger_error('The FullContactAPI class has been deprecated. Please use FullContact instead.', E_USER_NOTICE);
    }

    /**
     * Instead of using this implementation, you should create a
     *   FullContact_Person class and use the lookup method you prefer.
     *
     * @deprecated
     *
     * @param string|null $search - Search Term (Could be an email address or a phone number,
     *                            depending on the specified search type)
     * @param string|null $type   - Search Type (Specify the API search method to use.
     *                            E.g. email -- tested with email and phone)
     *
     * @return array<mixed> - All information associated with this email address
     */
    public function doLookup($search = null, $type = 'email')
    {
        if (is_null($search)) {
            throw new BaseException('To search, you must supply a search term.');
        }

        match ($type) {
            'email'   => $this->lookupByEmail($search),
            'phone'   => $this->lookupByPhone($search),
            'twitter' => $this->lookupByTwitter($search),
            default   => throw new ApiException("UnsupportedLookupMethodException: Invalid lookup method specified [{$type}]"),
        };

        $result             = json_decode($this->response_json, true);
        $result['is_error'] = !in_array($this->response_code, [200, 201, 204], true);

        return $result;
    }
}
