<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class TrackingEvent extends Event
{
    /**
     * @var Lead
     */
    private $contact;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ParameterBag
     */
    private $response;

    public function __construct(Lead $contact, Request $request, array $mtcSessionResponses)
    {
        $this->contact  = $contact;
        $this->request  = $request;
        $this->response = new ParameterBag($mtcSessionResponses);
    }

    public function getContact(): Lead
    {
        return $this->contact;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): ParameterBag
    {
        return $this->response;
    }
}
