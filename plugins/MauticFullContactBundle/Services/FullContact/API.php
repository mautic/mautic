<?php

class Services_FullContact_API extends Services_FullContact_Person
{
    public function __construct($api_key)
    {
        parent::__construct($api_key);
        trigger_error("The FullContactAPI class has been deprecated. Please use Services_FullContact instead.", E_USER_NOTICE);
    }

    /**
     * Instead of using this implementation, you should create a
     *   Services_FullContact_Person class and use the lookup method you prefer.
     *
     * @deprecated
     *
     * @param String - Search Term (Could be an email address or a phone number,
     *   depending on the specified search type)
     * @param String - Search Type (Specify the API search method to use.
     *   E.g. email -- tested with email and phone)
     * @param String (optional) - timeout
     *
     * @return Array - All information associated with this email address
     */
    public function doLookup($search = null, $type="email", $timeout = 30)
    {
        if (is_null($search)) {
            throw new Services_FullContact_Exception_Base("To search, you must supply a search term.");
        }
 
        switch($type)
        {
            case 'email':
                $this->lookupByEmail($search);
                break;
            case 'phone':
                $this->lookupByPhone($search);
                break;
            case 'twitter':
                $this->lookupByTwitter($search);
                break;
            default:
                throw new FullContactAPIException("UnsupportedLookupMethodException: Invalid lookup method specified [{$type}]");
                break;
        }

        $result = json_decode($this->response_json, true);
        $result['is_error'] = !in_array($this->response_code, array(200, 201, 204));

        return $result;
    }
}
