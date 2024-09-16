<?php

namespace Mautic\CoreBundle\IpLookup;

class MaxmindPrecisionLookup extends AbstractMaxmindLookup
{
    protected function getName(): string
    {
        return 'maxmind_precision';
    }
}
