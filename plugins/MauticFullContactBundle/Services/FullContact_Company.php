<?php

namespace MauticPlugin\MauticFullContactBundle\Services;

/**
 * This class handles everything related to the Company lookup API.
 *
 * @author   Adam Curtis <me@alc.im>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache
 */
class FullContact_Company extends FullContact_Base
{
    /**
     * Supported lookup methods.
     *
     * @var array
     */
    protected $_supportedMethods = ['domain'];
    protected $_resourceUri      = '/company.enrich';

    public function lookupByDomain($search)
    {
        $this->_execute(['domain' => $search, 'method' => 'domain'], ['domain' => $search]);

        return $this->response_obj;
    }

    public function lookupByName($search)
    {
        $this->_execute(['name' => $search, 'method' => 'name'], ['companyName' => $search]);

        return $this->response_obj;
    }
}
