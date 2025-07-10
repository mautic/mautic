<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class TrackingEvent extends Event
{
    private ParameterBag $response;

    public function __construct(
        private Lead $contact,
        private Request $request,
        array $mtcSessionResponses,
    ) {
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
