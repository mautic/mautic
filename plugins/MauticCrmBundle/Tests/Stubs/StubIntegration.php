<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Stubs;

use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;
use PHPUnit\Framework\MockObject\StubApi;

/**
 * @extends CrmAbstractIntegration<StubApi>
 */
class StubIntegration extends CrmAbstractIntegration
{
    public function getName(): string
    {
        return 'Stub';
    }
}
