<?php

namespace Mautic\CoreBundle\Tests\Unit;

use Mautic\CoreBundle\Loader\ParameterLoader;
use PHPUnit\Framework\TestCase;

class RememberMeTest extends TestCase
{
    public function testPersistentRemembermeKey(): void
    {
        // Ensure the defaultParameters are not statically cached.
        $p1             = new ParameterLoader();
        $reflectedClass = new \ReflectionClass($p1);
        $reflectedClass->setStaticPropertyValue('defaultParameters', []);

        // Create a kernel and set the parameterLoader to the one created above.
        $k1             = new \AppKernel('test', false);
        $reflectedClass = new \ReflectionClass($k1);
        $prop           = $reflectedClass->getProperty('parameterLoader');
        $prop->setAccessible(true);
        $prop->setValue($k1, $p1);

        // Boot the kernel and get the value of the rememberme_key value.
        $k1->boot();
        $v1 = $k1->getContainer()->getParameter('mautic.rememberme_key');

        // Ensure the defaultParameters are not statically cached.
        $p2             = new ParameterLoader();
        $reflectedClass = new \ReflectionClass($p2);
        $reflectedClass->setStaticPropertyValue('defaultParameters', []);

        // Create a kernel and set the parameterLoader to the one created above.
        $k2             = new \AppKernel('test', false);
        $reflectedClass = new \ReflectionClass($k2);
        $prop           = $reflectedClass->getProperty('parameterLoader');
        $prop->setAccessible(true);
        $prop->setValue($k2, $p2);

        // Boot the kernel and get the value of the rememberme_key value.
        $k2->boot();
        $v2 = $k2->getContainer()->getParameter('mautic.rememberme_key');

        $this->assertSame($v1, $v2);
    }
}
