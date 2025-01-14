<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Stubs;

use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;

class StubIntegration extends CrmAbstractIntegration
{
    public function getName()
    {
        return 'Stub';
    }
}
