<?php

namespace MauticPlugin\MauticFullContactBundle\Services;

/**
 * This class handles everything related to the Company lookup API.
 *
 * @author   Adam Curtis <me@alc.im>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache
 */
class FullContact_Batch extends FullContact_Base
{
    protected $_resourceUri = '/batch.json';

    /**
     * @param array $requests
     *
     * @throws \MauticPlugin\MauticFullContactBundle\Exception\FullContact_Exception_NoCredit
     * @throws \MauticPlugin\MauticFullContactBundle\Exception\FullContact_Exception_NotImplemented
     */
    public function sendRequests($requests)
    {
        $this->_execute([], ['requests' => $requests]);

        return $this->response_obj;
    }
}
