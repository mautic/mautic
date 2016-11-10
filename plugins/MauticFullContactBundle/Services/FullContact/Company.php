<?php

/**
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * This class handles everything related to the Company lookup API.
 *
 * @package  Services\FullContact
 * @author   Adam Curtis <me@alc.im>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache
 */
class Services_FullContact_Company extends Services_FullContact
{
    /**
     * Supported lookup methods
     * @var $_supportedMethods
     */
    protected $_supportedMethods = array('domain');
    protected $_resourceUri = '/company/lookup.json';

    public function lookupByDomain($search)
    {
        $this->_execute(array('domain' => $search, 'method' => 'domain'));

        return $this->response_obj;
    }
}
