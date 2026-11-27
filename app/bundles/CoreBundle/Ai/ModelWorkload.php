<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

enum ModelWorkload: string
{
    case Routine = 'routine';
    case Complex = 'complex';
}
