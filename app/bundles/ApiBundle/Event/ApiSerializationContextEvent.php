<?php

namespace Mautic\ApiBundle\Event;

use FOS\RestBundle\Context\Context;
use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpFoundation\Request;

class ApiSerializationContextEvent extends CommonEvent
{
    protected Context $context;

    private Request $request;

    public function __construct(Context $context, Request $request)
    {
        $this->context                 = $context;
        $this->request                 = $request;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @param Context
     */
    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
