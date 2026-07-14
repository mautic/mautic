<?php

namespace MauticPlugin\MauticFullContactBundle\Services;

use MauticPlugin\MauticFullContactBundle\Exception\NoCreditException;
use MauticPlugin\MauticFullContactBundle\Exception\NotImplementedException;

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
     * @throws NoCreditException
     * @throws NotImplementedException
     */
    public function sendRequests($requests)
    {
        $this->_execute([], ['requests' => $requests]);

        return $this->response_obj;
    }
}
