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
     * @var array
     */
    protected $entityRequestParameters;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param object  $entity
     * @param array   $entityRequestParameters
     * @param Request $request
     */
    public function __construct($entity, array $entityRequestParameters, Request $request)
    {
        $this->entity                  = $entity;
        $this->entityRequestParameters = $entityRequestParameters;
        $this->request                 = $request;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return array
     */
    public function getEntityRequestParameters()
    {
        return $this->entityRequestParameters;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
