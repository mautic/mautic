<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpFoundation\Request;

class ApiEntityEvent extends CommonEvent
{
    /**
     * @var object
     */
    protected $entity;

    /**
     * @var Request
     */
    private $request;

    public function __construct($entity, Request $request)
    {
        $this->entity  = $entity;
        $this->request = $request;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
