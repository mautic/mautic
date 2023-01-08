<?php

namespace Mautic\CoreBundle\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Functional extends \Codeception\Module
{
    public function getParameterFromContainer($service)
    {
        return $this->getModule('Symfony2')->_getContainer()->getParameter($service);
    }
}
