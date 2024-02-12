<?php

namespace MauticPlugin\MauticFullContactBundle\Services;

/**
 * This class just tells us what icons we have available.
 *
 * @author   Keith Casey <contrib@caseysoftware.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache
 */
class FullContact_Icon extends FullContact_Base
{
    protected $_supportedMethods = ['available'];

    protected $_resourceUri      = '/icon/';

    public function available()
    {
        $this->_execute(['method' => 'available']);

        return $this->response_obj;
    }
}
