<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DTO;

enum TokenLabelFormat: string
{
    case SIMPLE_PREFIX = 'simple';      // "Form: My Form"
    case LINK_WITH_ID  = 'link_with_id'; // "a:Page: alias (123)"
}
