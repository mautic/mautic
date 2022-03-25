<?php

namespace Mautic\CoreBundle\Tests\Functional;

    use Liip\FunctionalTestBundle\Test\WebTestCase;

        class RememberMeTest extends WebTestCase
        {
            public function testRemembermeLifetime(): void
            {
                $parameterLoader    = new \Mautic\CoreBundle\Loader\ParameterLoader();
                $this->assertGreaterThan(0, $parameterLoader->getParameterBag()->get('rememberme_lifetime'));
            }
        }
