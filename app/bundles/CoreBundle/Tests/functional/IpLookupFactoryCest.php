<?php

namespace Mautic\CoreBundle;

class IpLookupFactoryCest
{
    public function ensureGettingServiceFromFactoryProvidesInstance(FunctionalTester $I)
    {
        $ipFactory        = $I->grabService('mautic.ip_lookup.factory');
        $ipLookupServices = $I->grabService('mautic.config')->getParameter('ip_lookup_services');

        foreach ($ipLookupServices as $service => $details) {
            $instance = $ipFactory->getService($service);

            $I->assertInstanceOf(
                $details['class'],
                $instance,
                sprintf('Expected %s for service %s but received %s instead', $details['class'], $service, get_class($instance))
            );
        }
    }
}
