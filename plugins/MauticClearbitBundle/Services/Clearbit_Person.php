<?php

namespace MauticPlugin\MauticClearbitBundle\Services;

/**
 * This class handles everything related to the Person lookup API.
 */
class Clearbit_Person extends Clearbit_Base
{
    protected $_resourceUri = '/people/find';

    protected $_baseUri     = 'https://person.clearbit.com/';

    public function lookupByEmail($search)
    {
        $this->_execute(['email' => $search]);

        return $this->response_obj;
    }
}
