<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\IpLookup;

final class MaxmindCountryLookup extends AbstractMaxmindLookup
{
    protected function getName(): string
    {
        return 'maxmind_country';
    }
}
