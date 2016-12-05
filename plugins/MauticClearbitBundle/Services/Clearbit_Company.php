<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticClearbitBundle\Services;

/**
 * This class handles everything related to the Company lookup API.
 */
class Clearbit_Company extends Clearbit_Base
{
    public function __construct($api_key)
    {
        parent::__construct($api_key);
        $this->_baseUri     = 'https://company.clearbit.com/';
        $this->_resourceUri = '/companies/find';
    }

    public function lookupByDomain($search)
    {
        $this->_execute(['domain' => $search]);

        return $this->response_obj;
    }
}
