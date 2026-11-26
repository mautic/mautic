<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

enum ModelTier: int
{
    case Economy = 0;
    case Standard = 1;
    case Advanced = 2;
}
