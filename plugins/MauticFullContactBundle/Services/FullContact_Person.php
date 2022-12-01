<?php

namespace MauticPlugin\MauticFullContactBundle\Services;

/**
 * This class handles everything related to the Person lookup API.
 *
 * @author   Keith Casey <contrib@caseysoftware.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache
 */
class FullContact_Person extends FullContact_Base
{
    /**
     * Supported lookup methods.
     *
     * @var array
     */
    protected $_supportedMethods = ['email', 'phone', 'twitter'];
    protected $_resourceUri      = '/person.enrich';

    public function lookupByEmail($search)
    {
        $this->_execute(['email' => $search, 'method' => 'email'], ['email' => $search]);

        return $this->response_obj;
    }

    public function lookupByEmailMD5($search)
    {
        $this->_execute(['emailMD5' => $search, 'method' => 'email']);

        return $this->response_obj;
    }

    public function lookupByPhone($search)
    {
        $this->_execute(['phone' => $search, 'method' => 'phone'], ['phone' => $search]);

        return $this->response_obj;
    }

    public function lookupByTwitter($search)
    {
        $this->_execute(['twitter' => $search, 'method' => 'twitter'], [
          'profiles' => [
            'service'  => 'twitter',
            'username' => $search,
          ],
        ]);

        return $this->response_obj;
    }
}
