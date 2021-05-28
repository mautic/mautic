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
