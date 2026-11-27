<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

enum ProviderLocation: string
{
    case Local = 'local';
    case Cloud = 'cloud';
}
