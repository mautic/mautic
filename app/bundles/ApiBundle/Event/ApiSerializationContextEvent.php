<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Event;

use FOS\RestBundle\Context\Context;
use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpFoundation\Request;

final class ApiSerializationContextEvent extends CommonEvent
{
    public function __construct(private Context $context, private Request $request)
    {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
