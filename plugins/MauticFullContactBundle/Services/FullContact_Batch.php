<?php

/*
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at.
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
