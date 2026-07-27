<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

enum BuildJsScope
{
    case RUNTIME;
    case ESSENTIAL;
    case TRACKING;
}
